<?php
/**
 * PRUEBA EN DUMMY: eventos y ludoteca apagados por web (404 en vez de la página).
 *   sudo -u www-data php vendor/zaca/events/Test/dummy/ocultar_en_francia.php
 *
 * Dummy tiene la web `fr` (website 2, store view 2) pero ningún vhost que la sirva, así que:
 *   A/B) se recorre el front controller real de Magento (routers + plugins + acciones) dentro de un proceso por
 *        petición, con MAGE_RUN_CODE=fr / base, y se mira a qué módulo/controlador/acción acaba llegando la URL
 *        y qué código HTTP devuelve. Los interruptores se escriben en core_config_data (website fr = 0) y se
 *        restauran al terminar aunque algo falle.
 *   C)   HTTP real contra el Varnish local, apagando los interruptores en la web base un momento.
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../../../../app/bootstrap.php';

$opts = getopt('', ['store::', 'path::', 'method::']);

const PATHS = [
    'zaca_events/general/enabled',
    'zaca_events/ludoteca/enabled',
];

/* ───────────────────────── Hijo: una tienda, una URL ───────────────────────── */
if (isset($opts['store'])) {
    $storeId = (int) $opts['store'];
    $path = (string) $opts['path'];
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['REQUEST_METHOD'] = $opts['method'] ?? 'GET';
    // La web fr tiene su propia URL base (zacatrus.fr): con otro host Magento redirige a ella antes de enrutar.
    $host = $storeId === 2 ? 'zacatrus.fr' : 'dummy.zacatrus.es';
    $_SERVER['HTTP_HOST'] = $host;
    $_SERVER['SERVER_NAME'] = $host;
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = BP . '/pub/index.php';
    $params = $_SERVER;
    $params[\Magento\Store\Model\StoreManager::PARAM_RUN_TYPE] = 'website';
    $params[\Magento\Store\Model\StoreManager::PARAM_RUN_CODE] = $storeId === 2 ? 'fr' : 'base';

    $out = ['store' => $storeId, 'path' => $path];
    try {
        $bootstrap = Bootstrap::create(BP, $params);
        $om = $bootstrap->getObjectManager();
        $app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
        $out['store_actual'] = (int) $om->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId();
        $out['events_enabled'] = $om->get(\Zaca\Events\Helper\Data::class)->isEventsEnabled();
        $out['ludoteca_enabled'] = $om->get(\Zaca\Events\Helper\Data::class)->isLudotecaEnabled();
        $request = $om->get(\Magento\Framework\App\Request\Http::class);
        try {
            $response = $app->launch();
            $out['http'] = $response->getHttpResponseCode();
        } catch (\Throwable $e) {
            // Dentro de la CLI el render de la página puede fallar (plugins ajenos); lo que importa es adónde enrutó.
            $out['launch_error'] = get_class($e) . ': ' . substr($e->getMessage(), 0, 160);
        }
        $out['route'] = $request->getModuleName() . '/' . $request->getControllerName() . '/' . $request->getActionName();
        $out['location'] = $om->get(\Magento\Framework\App\Response\Http::class)->getHeader('Location')
            ? (string) $om->get(\Magento\Framework\App\Response\Http::class)->getHeader('Location')->getFieldValue() : null;
    } catch (\Throwable $e) {
        $out['error'] = get_class($e) . ': ' . substr($e->getMessage(), 0, 200);
    }
    echo 'RESULT ' . json_encode($out) . "\n";
    exit(0);
}

/* ───────────────────────── Padre ───────────────────────── */
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');
$writer = $om->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
$typeList = $om->get(\Magento\Framework\App\Cache\TypeListInterface::class);
$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();
$table = $resource->getTableName('core_config_data');

$pass = 0; $fail = 0;
$check = function (string $name, bool $ok, $detail = null) use (&$pass, &$fail) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . ($detail !== null ? '  -> ' . (is_scalar($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE)) : '') . "\n";
    $ok ? $pass++ : $fail++;
};
$flush = function () use ($typeList) {
    $typeList->cleanType('config');
    $typeList->cleanType('full_page');
    $typeList->cleanType('layout');
    $typeList->cleanType('block_html');
};
$child = function (int $storeId, string $path, string $method = 'GET'): array {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --store=' . $storeId
        . ' --path=' . escapeshellarg($path) . ' --method=' . $method . ' 2>&1';
    $raw = (string) shell_exec($cmd);
    foreach (explode("\n", $raw) as $line) {
        if (strpos($line, 'RESULT ') === 0) {
            return json_decode(substr($line, 7), true) ?: [];
        }
    }
    echo "  (el hijo $storeId $path no devolvió RESULT):\n" . substr($raw, -1500) . "\n";
    return [];
};
$http = function (string $path, string $method = 'GET', bool $ajax = false) {
    // Un POST sin form_key lo corta antes el CSRF de Magento (302), antes de mirar a qué acción llega: se manda uno válido.
    $post = $method === 'POST' ? " -H 'Cookie: form_key=abcdefghijklmnop' -d 'form_key=abcdefghijklmnop'" : '';
    $post .= $ajax ? " -H 'X-Requested-With: XMLHttpRequest'" : '';
    $cmd = "curl -s -o /dev/null -w '%{http_code}' -X $method$post -H 'Host: dummy.zacatrus.es' -H 'X-Forwarded-Proto: https' "
        . escapeshellarg('http://127.0.0.1:6081' . $path . (strpos($path, '?') === false ? '?' : '&') . 'zz=' . uniqid());
    return trim((string) shell_exec($cmd));
};
$esNoroute = fn(array $r) => ($r['route'] ?? '') === 'cms/noroute/index';

// Estado previo de las filas que se van a tocar, para devolverlas tal cual.
$saved = $conn->fetchAll($conn->select()->from($table)->where('path IN (?)', PATHS));
echo 'filas previas de los interruptores: ' . json_encode(array_map(fn($r) => [$r['scope'], $r['scope_id'], $r['path'], $r['value']], $saved)) . "\n";

$store = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$frWebsiteId = (int) $store->getStore(2)->getWebsiteId();
$baseWebsiteId = (int) $store->getStore(1)->getWebsiteId();
echo "web fr = id $frWebsiteId; web base = id $baseWebsiteId\n";

$urls = [
    'eventos' => '/eventos',
    'events literal' => '/events',
    'eventos/index/register' => '/eventos/index/register',
    'eventos/slug de tienda' => '/eventos/madrid',
    'events/index/index' => '/events/index/index',
    'ludoteca' => '/ludoteca',
    'ludoteca/tienda' => '/ludoteca/madrid',
];

try {
    // ── A) Todo encendido en el ámbito por defecto: es y fr enrutan a los controladores del módulo ──
    foreach (PATHS as $p) {
        $writer->save($p, '1', 'default', 0);
    }
    $flush();
    echo "\n== A) interruptores a 1 (por defecto) ==\n";
    foreach (['es' => 1, 'fr' => 2] as $nombre => $sid) {
        $r = $child($sid, '/eventos');
        echo "  $nombre /eventos: " . json_encode($r) . "\n";
        $check("A $nombre: /eventos llega al módulo (events/index/index)", ($r['route'] ?? '') === 'events/index/index' && ($r['store_actual'] ?? 0) === $sid, $r['error'] ?? $r['route'] ?? null);
        $r = $child($sid, '/ludoteca');
        $check("A $nombre: /ludoteca llega al módulo (ludoteca/ludoteca/index)", ($r['route'] ?? '') === 'ludoteca/ludoteca/index', $r['error'] ?? $r['route'] ?? null);
    }

    // ── B) Apagado solo en la web fr ──
    echo "\n== B) dos interruptores a 0 en la web fr (la base sigue a 1) ==\n";
    foreach (PATHS as $p) {
        $writer->save($p, '0', 'websites', $frWebsiteId);
    }
    $flush();
    $r = $child(2, '/eventos');
    $check('B fr: los interruptores valen 0 y es la tienda 2', ($r['store_actual'] ?? 0) === 2 && ($r['events_enabled'] ?? true) === false && ($r['ludoteca_enabled'] ?? true) === false, $r);
    foreach ($urls as $nombre => $url) {
        $r = $child(2, $url);
        echo "  fr $url: " . json_encode($r) . "\n";
        $check("B fr: $nombre → 404 de CMS", $esNoroute($r) && ($r['http'] ?? 404) === 404 && empty($r['location']), $r['error'] ?? [$r['route'] ?? null, $r['http'] ?? null, $r['location'] ?? null]);
    }
    $r = $child(2, '/eventos/index/register', 'POST');
    $check('B fr: POST a eventos/index/register → 404 de CMS (no apunta a nadie)', $esNoroute($r), $r['error'] ?? $r['route'] ?? null);
    foreach (['/eventos', '/events/index/index', '/ludoteca'] as $url) {
        $r = $child(1, $url);
        echo "  es $url: " . json_encode($r) . "\n";
        $check("B es: $url sigue llegando al módulo", !$esNoroute($r) && in_array(explode('/', $r['route'] ?? '')[0], ['events', 'ludoteca'], true), $r['error'] ?? $r['route'] ?? null);
    }
} finally {
    $conn->delete($table, ['path IN (?)' => PATHS]);
    foreach ($saved as $row) {
        $conn->insert($table, ['scope' => $row['scope'], 'scope_id' => $row['scope_id'], 'path' => $row['path'], 'value' => $row['value']]);
    }
    $flush();
    echo "\n(interruptores devueltos a como estaban)\n";
}

// ── C) HTTP real: eventos y ludoteca apagados en la web base (la que sirve dummy) y vuelta ──
echo "\n== C) HTTP contra Varnish local, interruptores en la web base (id $baseWebsiteId) ==\n";
try {
    foreach (PATHS as $p) {
        $writer->save($p, '1', 'websites', $baseWebsiteId);
    }
    $flush();
    $on = [];
    foreach (['/eventos', '/ludoteca'] as $u) {
        $on[$u] = $http($u);
    }
    echo '  encendido: ' . json_encode($on) . "\n";
    $check('C encendido: /eventos da 200', $on['/eventos'] === '200', $on['/eventos']);
    $check('C encendido: /ludoteca da 200', $on['/ludoteca'] === '200', $on['/ludoteca']);

    foreach (PATHS as $p) {
        $writer->save($p, '0', 'websites', $baseWebsiteId);
    }
    $flush();
    $off = [];
    foreach (['/eventos', '/eventos/', '/events', '/events/index/index', '/eventos/madrid', '/eventos/index/register', '/ludoteca', '/ludoteca/madrid'] as $u) {
        $off[$u] = $http($u);
    }
    $off['POST /eventos/index/register'] = $http('/eventos/index/register', 'POST');
    $off['POST ajax /eventos/index/register'] = $http('/eventos/index/register', 'POST', true);
    $off['POST ajax /eventos/index/unregister'] = $http('/eventos/index/unregister', 'POST', true);
    $off['POST ajax /ludoteca/ludoteca/reserve'] = $http('/ludoteca/ludoteca/reserve', 'POST', true);
    echo '  apagado: ' . json_encode($off) . "\n";
    foreach ($off as $u => $code) {
        $check("C apagado: $u da 404", $code === '404', $code);
    }
} finally {
    $conn->delete($table, ['path IN (?)' => PATHS]);
    foreach ($saved as $row) {
        $conn->insert($table, ['scope' => $row['scope'], 'scope_id' => $row['scope_id'], 'path' => $row['path'], 'value' => $row['value']]);
    }
    $flush();
}

echo "\nRESUMEN: $pass PASS, $fail FAIL\n";
exit($fail ? 1 : 0);
