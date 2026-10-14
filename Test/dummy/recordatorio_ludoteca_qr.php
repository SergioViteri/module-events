<?php
/**
 * PRUEBA EN DUMMY: URLs del correo de recordatorio (QR y enlaces) cuando se envía desde cron.
 *   sudo -u www-data php vendor/zaca/events/Test/dummy/recordatorio_ludoteca_qr.php [--contexto=fr|admin|normal] [--conservar] [--limpiar]
 *
 * Qué hace:
 *   1. Se coloca en el área `crontab` y deja como tienda actual una que NO es la del cliente, que es lo que le pasa al
 *      cron en producción cuando otro trabajo deja la tienda en el admin: en dummy la CLI cae por sí sola en la tienda 1
 *      y sin este paso no se reproduce nada.
 *        --contexto=fr     tienda actual = 2 (web fr, zacatrus.fr). Reproduce limpio: los clientes de la web es
 *                          recibirían URLs de zacatrus.fr. Es el valor por defecto.
 *        --contexto=admin  tienda actual = 0 (admin) con un host de admin de mentira como URL base. En dummy la tienda 0
 *                          no tiene tema y la plantilla puede fallar con «theme_dir» antes de llegar a las URLs.
 *        --contexto=normal tienda actual = 1, sin tocar nada.
 *   2. Crea tres reservas confirmadas para mañana (un cliente de la web es, uno de la web es con store_id 0 y uno de la
 *      web fr), todas marcadas con el prefijo `zpruebaqr` en unsubscribe_code para poder borrarlas.
 *   3. Ejecuta el cron REAL de recordatorios (`Cron\SendLudotecaReminders::execute`), con la única diferencia de que
 *      «días antes» vale 1 sin tocar la configuración. El transporte de correo se sustituye por uno que sólo guarda el
 *      mensaje: no sale ningún correo de dummy.
 *   4. Para cada correo muestra el `src` del <img> del QR y el enlace de cancelar, y comprueba que apuntan al host
 *      de la tienda del cliente (nunca al del contexto).
 *   5. Con el recordatorio de EVENTOS (Helper\Email::sendReminderEmail) hace lo mismo con el enlace de baja.
 *   6. Borra lo creado, salvo con --conservar (para poder pedir el QR por HTTP desde fuera). --limpiar sólo borra.
 */
use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;

// Dentro de vendor/zaca/events/Test/dummy/ (validate-on-dummy) o copiado suelto a /tmp en dummy.
require is_file(__DIR__ . '/../../../../../app/bootstrap.php')
    ? __DIR__ . '/../../../../../app/bootstrap.php'
    : '/var/www/zacatrus/current/app/bootstrap.php';

const PREFIJO = 'zpruebaqr';
const HOST_ADMIN = 'https://admin.zacatrus.test/';

/** Transporte que no envía: guarda el mensaje. */
class ZacaPruebaTransporte implements \Magento\Framework\Mail\TransportInterface
{
    public static array $enviados = [];
    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function sendMessage()
    {
        self::$enviados[] = $this->message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}

class ZacaPruebaFabricaTransporte
{
    public function create(array $data = [])
    {
        return new ZacaPruebaTransporte($data['message']);
    }
}

/** Cron real, pero con «1 día antes» sin tocar core_config_data. */
class ZacaPruebaAyudanteUnDia extends \Zaca\Events\Helper\Data
{
    public function getReminderDaysBefore(?int $storeId = null): array
    {
        return [1];
    }
}

/** Da a la tienda admin la URL base de un host de admin; el resto de valores los lee tal cual. */
class ZacaPruebaConfigAdmin implements ScopeConfigInterface
{
    private ScopeConfigInterface $inner;

    public function __construct(ScopeConfigInterface $inner)
    {
        $this->inner = $inner;
    }

    public function getValue($path = null, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        $rutasBase = [
            'web/unsecure/base_url', 'web/secure/base_url',
            'web/unsecure/base_link_url', 'web/secure/base_link_url',
        ];
        if (in_array($path, $rutasBase, true) && $scope === 'store' && $scopeCode === 'admin') {
            return HOST_ADMIN;
        }
        return $this->inner->getValue($path, $scope, $scopeCode);
    }

    public function isSetFlag($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return $this->inner->isSetFlag($path, $scope, $scopeCode);
    }
}

function cuerpoHtml($message): string
{
    $body = $message->getBody();
    if (is_object($body) && method_exists($body, 'getParts')) {
        $partes = [];
        foreach ($body->getParts() as $p) {
            $partes[] = $p->getRawContent();
        }
        return implode("\n", $partes);
    }
    return quoted_printable_decode((string) $message->getRawMessage());
}

function propiedad(object $objeto, string $nombre, ?string $clase = null): \ReflectionProperty
{
    $p = new \ReflectionProperty($clase ?? get_class($objeto), $nombre);
    $p->setAccessible(true);
    return $p;
}

function hostDe(string $url): string
{
    return (string) parse_url($url, PHP_URL_HOST);
}

$opts = getopt('', ['contexto::', 'conservar', 'limpiar']);
$contexto = $opts['contexto'] ?? 'fr';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$limpiar = function () use ($conn) {
    $conn->delete('zaca_events_table_booking', ['unsubscribe_code LIKE ?' => PREFIJO . '%']);
};
if (isset($opts['limpiar'])) {
    $limpiar();
    echo "Reservas de prueba borradas.\n";
    exit(0);
}

/* ── Área y tienda: lo que ve el cron ── */
$om->get(\Magento\Framework\App\State::class)->setAreaCode(Area::AREA_CRONTAB);
$om->configure($om->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class)->load(Area::AREA_CRONTAB));
$sm = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
if ($contexto === 'fr') {
    $sm->setCurrentStore(2);
} elseif ($contexto === 'admin') {
    $admin = $sm->getStore(0);
    propiedad($admin, '_config', \Magento\Store\Model\Store::class)
        ->setValue($admin, new ZacaPruebaConfigAdmin($om->get(ScopeConfigInterface::class)));
    $sm->setCurrentStore(0);
}
$baseContexto = $sm->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
echo "contexto={$contexto} tienda_actual=" . $sm->getStore()->getId() . ' (' . $sm->getStore()->getCode() . ')'
    . ' base_del_contexto=' . $baseContexto . "\n";

/* ── Datos ── */
$limpiar();
$casos = [
    // etiqueta => [filtro de cliente, host esperado de la URL]
    'cliente web es (store 1)' => ['website_id = 1 AND store_id = 1', 'dummy.zacatrus.es'],
    'cliente web es (store_id 0)' => ['website_id = 1 AND store_id = 0', 'dummy.zacatrus.es'],
    'cliente web fr (store 2)' => ['website_id = 2 AND store_id = 2', 'zacatrus.fr'],
];
$manana = (new \DateTimeImmutable('today'))->modify('+1 day')->format('Y-m-d');
$timeSlot = $conn->fetchOne('SELECT time_slot_id FROM zaca_events_time_slot LIMIT 1');
$reservas = [];
$n = 0;
foreach ($casos as $etiqueta => [$filtro, $hostEsperado]) {
    $cliente = $conn->fetchRow(
        "SELECT entity_id, email, website_id, store_id FROM customer_entity WHERE $filtro AND email IS NOT NULL ORDER BY entity_id LIMIT 1"
    );
    if (!$cliente) {
        echo "SIN CLIENTE para «$etiqueta», se omite\n";
        continue;
    }
    $conn->insert('zaca_events_table_booking', [
        'location_id' => 1,
        'customer_id' => (int) $cliente['entity_id'],
        'booking_date' => $manana,
        'status' => 'confirmed',
        'phone_number' => '600000000',
        'unsubscribe_code' => PREFIJO . (++$n) . '-' . bin2hex(random_bytes(6)),
    ]);
    $id = (int) $conn->lastInsertId();
    if ($timeSlot) {
        $conn->insert('zaca_events_table_booking_slot', [
            'booking_id' => $id, 'location_id' => 1, 'booking_date' => $manana,
            'time_slot_id' => (int) $timeSlot, 'tables_count' => 1,
        ]);
    }
    $reservas[$id] = ['etiqueta' => $etiqueta, 'email' => $cliente['email'], 'host' => $hostEsperado, 'codigo' => PREFIJO . $n];
}

/* ── Correo: no sale nada de dummy ── */
$ludotecaEmail = $om->get(\Zaca\Events\Helper\LudotecaEmail::class);
$builder = propiedad($ludotecaEmail, 'transportBuilder', \Zaca\Events\Helper\LudotecaEmail::class)->getValue($ludotecaEmail);
propiedad($builder, 'mailTransportFactory', \Magento\Framework\Mail\Template\TransportBuilder::class)
    ->setValue($builder, new ZacaPruebaFabricaTransporte());

/* ── Cron real ── */
$cron = $om->get(\Zaca\Events\Cron\SendLudotecaReminders::class);
propiedad($cron, 'helper', \Zaca\Events\Cron\SendLudotecaReminders::class)
    ->setValue($cron, (new \ReflectionClass(ZacaPruebaAyudanteUnDia::class))->newInstanceWithoutConstructor());

$fallos = 0;
try {
    $enviados = $cron->execute();
    echo "cron: $enviados recordatorio(s) enviados (esperados: " . count($reservas) . ")\n";
    if ($enviados !== count($reservas)) {
        $fallos++;
    }
    foreach (ZacaPruebaTransporte::$enviados as $message) {
        $html = cuerpoHtml($message);
        $para = $message->getTo()[0]->getEmail();
        // Hay clientes de prueba con el mismo correo: se identifica la reserva por el código del enlace de cancelar.
        preg_match('/href="([^"]*cancel[^"]*)"/', $html, $cancel);
        $id = null;
        foreach ($reservas as $rid => $r) {
            if (strpos($cancel[1] ?? '', $r['codigo'] . '-') !== false) {
                $id = $rid;
                break;
            }
        }
        if ($id === null) {
            echo "correo a $para sin reserva de prueba asociada (enlace de cancelar: " . ($cancel[1] ?? 'ninguno') . ")\n";
            $fallos++;
            continue;
        }
        $r = $reservas[$id];
        preg_match('/<img[^>]+src="([^"]*qrcode[^"]*)"/', $html, $qr);
        $qrUrl = html_entity_decode($qr[1] ?? '');
        $cancelUrl = html_entity_decode($cancel[1] ?? '');
        $okQr = hostDe($qrUrl) === $r['host'] && strpos($qrUrl, 'qrcode?id=' . $id) !== false;
        $okCancel = hostDe($cancelUrl) === $r['host'];
        echo sprintf(
            "[%s] reserva %d → %s\n  QR      : %s  %s\n  cancelar: %s  %s\n  remitente: %s\n",
            $r['etiqueta'], $id, $para,
            $qrUrl, $okQr ? 'OK' : 'FALLO (esperado host ' . $r['host'] . ' y qrcode?id=' . $id . ')',
            $cancelUrl, $okCancel ? 'OK' : 'FALLO (esperado host ' . $r['host'] . ')',
            ($message->getFrom()[0] ?? null) ? $message->getFrom()[0]->getName() . ' <' . $message->getFrom()[0]->getEmail() . '>' : 'sin remitente'
        );
        $fallos += ($okQr ? 0 : 1) + ($okCancel ? 0 : 1);
    }

    /* ── El constructor de URLs compartido no debe quedarse con la tienda del último correo ──
     * (antes del recordatorio de eventos, que cambia la tienda actual a propósito) */
    $baseTras = $om->get(\Magento\Framework\UrlInterface::class)->getBaseUrl(['_secure' => true]);
    $okTras = hostDe($baseTras) === hostDe($baseContexto);
    echo "urlBuilder tras los envíos: $baseTras  " . ($okTras ? 'OK (sigue en la tienda del contexto)' : 'FALLO (se quedó con otra tienda)') . "\n";
    $fallos += $okTras ? 0 : 1;

    /* ── Recordatorio de EVENTOS: enlace de baja ── */
    ZacaPruebaTransporte::$enviados = [];
    $emailEventos = $om->get(\Zaca\Events\Helper\Email::class);
    $builderE = propiedad($emailEventos, 'transportBuilder')->getValue($emailEventos);
    propiedad($builderE, 'mailTransportFactory', \Magento\Framework\Mail\Template\TransportBuilder::class)
        ->setValue($builderE, new ZacaPruebaFabricaTransporte());
    $reg = $conn->fetchRow(
        'SELECT r.registration_id, c.website_id FROM zaca_events_registration r JOIN customer_entity c ON c.entity_id = r.customer_id '
        . 'JOIN zaca_events_meet m ON m.meet_id = r.meet_id WHERE c.website_id = 1 ORDER BY r.registration_id LIMIT 1'
    );
    if (!$reg) {
        echo "eventos: dummy no tiene ninguna inscripción de la web es, se omite\n";
    } else {
        $registro = $om->get(\Zaca\Events\Api\RegistrationRepositoryInterface::class)->getById((int) $reg['registration_id']);
        $ok = $emailEventos->sendReminderEmail($registro, 1);
        $message = ZacaPruebaTransporte::$enviados[0] ?? null;
        if (!$ok || !$message) {
            echo "eventos: sendReminderEmail devolvió " . var_export($ok, true) . " y no hay correo capturado\n";
            $fallos++;
        } else {
            preg_match('/href="([^"]*unsubscribe[^"]*)"/', cuerpoHtml($message), $baja);
            $bajaUrl = html_entity_decode($baja[1] ?? '');
            $okBaja = hostDe($bajaUrl) === 'dummy.zacatrus.es';
            echo "eventos: inscripción {$reg['registration_id']}\n  baja    : $bajaUrl  "
                . ($okBaja ? 'OK' : 'FALLO (esperado host dummy.zacatrus.es)') . "\n";
            $fallos += $okBaja ? 0 : 1;
        }
    }
} finally {
    if (isset($opts['conservar'])) {
        echo 'CONSERVADAS: ' . implode(',', array_keys($reservas)) . "\n";
    } else {
        $limpiar();
    }
}

echo $fallos === 0 ? "RESULTADO: OK\n" : "RESULTADO: $fallos FALLO(S)\n";
exit($fallos === 0 ? 0 : 1);
