<?php
/**
 * PRUEBA EN DUMMY: turnos de ludoteca con la opción «Lunes a sábado» (day_of_week = 9).
 *   sudo -u www-data php vendor/zaca/events/Test/dummy/turnos_lunes_a_sabado.php [--conservar] [--limpiar]
 *
 * Qué hace:
 *   1. Formulario del admin (Eventos / Turnos de ludoteca): renderiza el bloque real `Edit\Tab\Main` y comprueba que el
 *      selector «Día de la semana» trae «Lunes a sábado» (valor 9) entre «Días laborables» y «Lunes», y que en español
 *      se llama «Lunes a sábado». La columna del listado (Grid::renderDayOfWeek) muestra el mismo texto.
 *   2. Crea una tienda de prueba (prefijo `zpruebalunsab`, 3 mesas) con cinco turnos, guardados con el mismo
 *      `$model->setData(...)->save()` que usa el controlador Save (el valor llega como cadena '9'):
 *        A 10:00 Lunes a sábado · B 16:00 Días laborables · C 18:00 Todos los días · D 11:00 sólo domingo
 *        E 12:00 Lunes a sábado pero DESACTIVADO (no debe salir nunca).
 *   3. Con el servicio REAL `Service\Ludoteca\AvailabilityService` mira qué turnos ofrece cada día de la semana
 *      (próximos 14 días) y comprueba que A sale de lunes a sábado y NUNCA en domingo, que B sigue siendo sólo de
 *      lunes a viernes, que C sale siempre y que D sale sólo el domingo. También el calendario del mes: un domingo
 *      de una tienda con sólo un turno «Lunes a sábado» sale cerrado y el sábado no.
 *   4. Borra lo creado, salvo con --conservar. --limpiar sólo borra.
 *
 * El horizonte de reserva se amplía a 30 días con un ayudante de prueba (por defecto un cliente no socio sólo ve
 * hoy y mañana); no se toca ninguna configuración.
 */
use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;

// Dentro de vendor/zaca/events/Test/dummy/ (validate-on-dummy) o copiado suelto a /tmp en dummy.
require is_file(__DIR__ . '/../../../../../app/bootstrap.php')
    ? __DIR__ . '/../../../../../app/bootstrap.php'
    : '/var/www/zacatrus/current/app/bootstrap.php';

const PREFIJO = 'zpruebalunsab';

/** Igual que el helper real, pero con horizonte de 30 días para cualquiera. */
class ZacaPruebaAyudanteHorizonte extends \Zaca\Events\Helper\Data
{
    public function getMaxAdvanceDays(?int $customerId, ?int $storeId = null): int
    {
        return 30;
    }

    public function isClubFeatureAvailable(): bool
    {
        return false;
    }
}

$opts = getopt('', ['conservar', 'limpiar']);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode(Area::AREA_ADMINHTML);
$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();

$limpiar = function () use ($conn) {
    // Las FK en cascada se llevan los turnos de la tienda de prueba.
    $conn->delete('zaca_events_location', ['name LIKE ?' => PREFIJO . '%']);
};
if (isset($opts['limpiar'])) {
    $limpiar();
    echo "Datos de prueba borrados.\n";
    exit(0);
}

$pass = 0;
$fail = 0;
$check = function (string $nombre, bool $ok, $detalle = null) use (&$pass, &$fail) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $nombre
        . ($detalle !== null ? '  -> ' . (is_scalar($detalle) ? $detalle : json_encode($detalle, JSON_UNESCAPED_UNICODE)) : '') . "\n";
    $ok ? $pass++ : $fail++;
};

$limpiar();

try {
    // ------------------------------------------------------------------ 1. Formulario del admin
    echo "== 1. Formulario del admin\n";
    $translate = $om->get(\Magento\Framework\TranslateInterface::class);
    $translate->setLocale('es_ES');
    $translate->loadData(Area::AREA_ADMINHTML, true);

    $registry = $om->get(\Magento\Framework\Registry::class);
    $registry->register(
        \Zaca\Events\Controller\Adminhtml\LudotecaTimeSlot\Edit::REGISTRY_KEY,
        $om->create(\Zaca\Events\Model\Ludoteca\TimeSlotFactory::class)->create()
    );
    $layout = $om->get(\Magento\Framework\View\LayoutInterface::class);
    $bloque = $layout->createBlock(\Zaca\Events\Block\Adminhtml\LudotecaTimeSlot\Edit\Tab\Main::class);
    $bloque->toHtml();
    $selector = $bloque->getForm()->getElement('day_of_week');
    $html = $selector->getElementHtml();
    $opciones = [];
    foreach ($selector->getValues() as $o) {
        $opciones[(string) $o['value']] = (string) $o['label'];
    }
    echo "   opciones del selector: " . json_encode(array_values($opciones), JSON_UNESCAPED_UNICODE) . "\n";
    $check('el selector trae «Lunes a sábado» con valor 9', ($opciones['9'] ?? null) === 'Lunes a sábado', $opciones['9'] ?? 'no está');
    $check('el HTML del <select> tiene la <option value="9">',
        (bool) preg_match('~<option value="9"[^>]*>\s*Lunes a sábado\s*</option>~u', $html));
    $claves = array_keys($opciones);
    $check('orden: Todos los días, L-V, Lunes a sábado, Lunes…Domingo',
        $claves === ['', '8', '9', '1', '2', '3', '4', '5', '6', '7'], implode(',', $claves));
    $check('«Días laborables (L-V)» sigue ahí con valor 8', ($opciones['8'] ?? null) === 'Días laborables (L-V)');

    $rejilla = $om->create(\Zaca\Events\Block\Adminhtml\LudotecaTimeSlot\Grid::class);
    $render = $rejilla->renderDayOfWeek('9', null, null, false);
    $check('la columna del listado muestra «Lunes a sábado» para el valor 9', $render === 'Lunes a sábado', $render);

    // ------------------------------------------------------------------ 2. Datos
    echo "== 2. Datos de prueba\n";
    $conn->insert('zaca_events_location', [
        'name' => PREFIJO . ' tienda', 'code' => PREFIJO, 'url_key' => PREFIJO,
        'total_tables' => 3, 'is_active' => 1,
    ]);
    $tiendaId = (int) $conn->lastInsertId('zaca_events_location');
    $conn->insert('zaca_events_location', [
        'name' => PREFIJO . ' tienda solo L-S', 'code' => PREFIJO . 'b', 'url_key' => PREFIJO . 'b',
        'total_tables' => 3, 'is_active' => 1,
    ]);
    $tiendaSoloLS = (int) $conn->lastInsertId('zaca_events_location');

    $fabrica = $om->get(\Zaca\Events\Model\Ludoteca\TimeSlotFactory::class);
    $crearTurno = function (int $tienda, $dia, string $ini, string $fin, int $orden, int $activo = 1) use ($fabrica) {
        $m = $fabrica->create();
        // Como el controlador Save: setData con lo que llega del formulario (cadenas).
        $m->setData([
            'location_id' => (string) $tienda, 'day_of_week' => $dia, 'start_time' => $ini . ':00',
            'end_time' => $fin . ':00', 'sort_order' => (string) $orden, 'is_active' => (string) $activo,
        ]);
        $m->save();
        return (int) $m->getId();
    };
    $A = $crearTurno($tiendaId, '9', '10:00', '12:00', 10);
    $B = $crearTurno($tiendaId, '8', '16:00', '18:00', 20);
    $C = $crearTurno($tiendaId, null, '18:00', '20:00', 30);
    $D = $crearTurno($tiendaId, '7', '11:00', '13:00', 40);
    $E = $crearTurno($tiendaId, '9', '12:00', '14:00', 50, 0);
    $unico = $crearTurno($tiendaSoloLS, '9', '10:00', '12:00', 10);

    $guardado = $om->create(\Zaca\Events\Model\Ludoteca\TimeSlot::class)->load($A);
    $check('el turno A se guarda y se relee con day_of_week = 9',
        $guardado->getDayOfWeek() === 9, var_export($guardado->getDayOfWeek(), true));

    // ------------------------------------------------------------------ 3. Disponibilidad por día
    echo "== 3. Turnos que ofrece cada día\n";
    $ayudante = (new \ReflectionClass(ZacaPruebaAyudanteHorizonte::class))->newInstanceWithoutConstructor();
    $servicio = new \Zaca\Events\Service\Ludoteca\AvailabilityService($resource, $ayudante);
    $nombres = [1 => 'lun', 2 => 'mar', 3 => 'mié', 4 => 'jue', 5 => 'vie', 6 => 'sáb', 7 => 'dom'];
    $letras = [$A => 'A', $B => 'B', $C => 'C', $D => 'D', $E => 'E'];
    $hoy = new \DateTimeImmutable('today');
    $vistoDomingo = $vistoSabado = false;
    for ($i = 0; $i < 14; $i++) {
        $dia = $hoy->modify("+$i days");
        $n = (int) $dia->format('N');
        $filas = $servicio->availabilityForDate($tiendaId, $dia, null);
        $ids = array_map(static fn ($f) => $f['time_slot_id'], $filas);
        $vistos = implode('', array_map(static fn ($id) => $letras[$id] ?? '?', $ids));
        $esperado = ($n <= 6 ? 'A' : '') . ($n <= 5 ? 'B' : '') . 'C' . ($n === 7 ? 'D' : '');
        // Orden por sort_order: A(10) B(20) C(30) D(40)
        $check(sprintf('%s %s ofrece «%s»', $nombres[$n], $dia->format('Y-m-d'), $esperado), $vistos === $esperado, "vistos «$vistos»");
        $check(sprintf('%s %s: el turno desactivado E no sale', $nombres[$n], $dia->format('Y-m-d')), !in_array($E, $ids, true));
        $vistoDomingo = $vistoDomingo || $n === 7;
        $vistoSabado = $vistoSabado || $n === 6;
    }
    $check('en 14 días se han recorrido sábados y domingos', $vistoSabado && $vistoDomingo);

    // ------------------------------------------------------------------ 4. Calendario del mes
    echo "== 4. Calendario (tienda con sólo un turno «Lunes a sábado»)\n";
    // Este mes y el siguiente: lo que queda del mes puede no incluir ningún domingo.
    $siguiente = $hoy->modify('first day of next month');
    $mes = $servicio->monthCalendar($tiendaSoloLS, (int) $hoy->format('Y'), (int) $hoy->format('n'), null)
        + $servicio->monthCalendar($tiendaSoloLS, (int) $siguiente->format('Y'), (int) $siguiente->format('n'), null);
    $domingoCerrado = $sabadoAbierto = $lunesAbierto = null;
    foreach ($mes as $fecha => $estado) {
        $d = new \DateTimeImmutable($fecha);
        if ($d < $hoy || $d > $hoy->modify('+29 days')) {
            continue;
        }
        $n = (int) $d->format('N');
        if ($n === 7) {
            $domingoCerrado = ($domingoCerrado ?? true) && $estado === 'closed';
        } elseif ($n === 6) {
            $sabadoAbierto = ($sabadoAbierto ?? true) && $estado !== 'closed';
        } elseif ($n === 1) {
            $lunesAbierto = ($lunesAbierto ?? true) && $estado !== 'closed';
        }
    }
    echo '   estados (próximos 30 días): ' . json_encode(array_filter(
        $mes,
        static fn ($f) => $f >= $hoy->format('Y-m-d') && $f <= $hoy->modify('+29 days')->format('Y-m-d'),
        ARRAY_FILTER_USE_KEY
    )) . "\n";
    $check('todos los domingos del mes salen cerrados', $domingoCerrado === true, var_export($domingoCerrado, true));
    $check('los sábados (con horizonte) no salen cerrados', $sabadoAbierto === true, var_export($sabadoAbierto, true));
    $check('los lunes no salen cerrados', $lunesAbierto === true, var_export($lunesAbierto, true));
} catch (\Throwable $e) {
    $check('la prueba termina sin excepciones', false, get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
} finally {
    if (!isset($opts['conservar'])) {
        $limpiar();
        echo "Datos de prueba borrados.\n";
    }
}

echo "\n$pass PASS, $fail FAIL\n";
exit($fail > 0 ? 1 : 0);
