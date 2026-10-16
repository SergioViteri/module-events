<?php
/**
 * PRUEBA EN DUMMY: el carnet de socio encuentra también las reservas de ludoteca.
 *   sudo -u www-data php vendor/zaca/events/Test/dummy/carnet_reservas_ludoteca.php [--conservar] [--limpiar]
 *
 * Recorre el flujo REAL por HTTP contra el Varnish local de dummy (lo que hace el empleado con el móvil): mete el
 * código de tienda, abre el carnet de un cliente y sigue las redirecciones. Los datos se crean en la base de dummy
 * con el prefijo `zpruebacarnet` y se borran al terminar (con --conservar se dejan; --limpiar sólo borra).
 *
 *   1. Sólo reserva de hoy en la tienda      → redirige a /ludoteca/attendance?id=…&code=… y sale «Reserva válida»;
 *                                              se crea la fila en zaca_events_attendance con booking_id; al
 *                                              reescanear sale «Reserva ya registrada hoy».
 *   2. Evento y reserva el mismo día         → selector con las dos (evento: nombre y hora; reserva: «Ludoteca» y
 *                                              sus turnos); las dos opciones registran la asistencia.
 *   3. Reserva de otro día / de otra tienda / cancelada → mensaje de vacío.
 *   4. Sólo evento (lo de siempre)           → redirige a /<ruta>/index/attendance como antes.
 *   5. Ludoteca desactivada                  → no se buscan reservas: con sólo reserva sale el vacío, con evento y
 *                                              reserva se redirige al evento. El interruptor se devuelve a como estaba.
 */
use Magento\Framework\App\Bootstrap;

// Dentro de vendor/zaca/events/Test/dummy/ (validate-on-dummy) o copiado suelto a /tmp en dummy.
require is_file(__DIR__ . '/../../../../../app/bootstrap.php')
    ? __DIR__ . '/../../../../../app/bootstrap.php'
    : '/var/www/zacatrus/current/app/bootstrap.php';

const PREFIJO = 'zpruebacarnet';
const TIENDA_A = 1;   // Zacatrus Valencia, con turnos de ludoteca
const TIENDA_B = 2;   // otra tienda cualquiera
const INTERRUPTOR = 'zaca_events/ludoteca/enabled';
const TEXTO_VACIO = 'No hay ningún evento ni reserva de ludoteca de este cliente hoy en esta tienda.';

$opts = getopt('', ['conservar', 'limpiar']);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');
$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();
$helper = $om->get(\Zaca\Events\Helper\Data::class);
$writer = $om->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
$typeList = $om->get(\Magento\Framework\App\Cache\TypeListInterface::class);

$limpiar = function () use ($conn) {
    // Las FK en cascada se llevan los turnos, la asistencia y las inscripciones.
    $conn->delete('zaca_events_table_booking', ['unsubscribe_code LIKE ?' => PREFIJO . '%']);
    $conn->delete('zaca_events_meet', ['name LIKE ?' => PREFIJO . '%']);
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

/* ── HTTP contra el Varnish local, con las cookies a mano (las seguras no viajan por http://127.0.0.1) ── */
function peticion(string $metodo, string $url, array &$cookies, ?string $post = null): array
{
    $p = parse_url($url);
    $ruta = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
    if ($metodo === 'GET') {
        $ruta .= (strpos($ruta, '?') === false ? '?' : '&') . 'zz=' . uniqid();
    }
    $galletas = implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($cookies), $cookies));
    $fichero = tempnam(sys_get_temp_dir(), 'zh');
    $cmd = 'curl -s -D ' . escapeshellarg($fichero) . ' -X ' . $metodo
        . ($post !== null ? ' -d ' . escapeshellarg($post) : '')
        . " -H 'Host: dummy.zacatrus.es' -H 'X-Forwarded-Proto: https' -H 'User-Agent: zprueba-carnet'"
        . ($galletas !== '' ? ' -H ' . escapeshellarg('Cookie: ' . $galletas) : '')
        . ' ' . escapeshellarg('http://127.0.0.1:6081' . $ruta);
    $cuerpo = (string) shell_exec($cmd);
    $cabeceras = (string) file_get_contents($fichero);
    unlink($fichero);

    $r = ['url' => $ruta, 'status' => 0, 'location' => null, 'body' => $cuerpo];
    if (preg_match('#^HTTP/\S+ (\d+)#m', $cabeceras, $m)) {
        $r['status'] = (int) $m[1];
    }
    if (preg_match('/^Location:\s*(\S+)/mi', $cabeceras, $m)) {
        $r['location'] = $m[1];
    }
    if (preg_match_all('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/mi', $cabeceras, $todas, PREG_SET_ORDER)) {
        foreach ($todas as $c) {
            if ($c[2] === 'deleted') {
                unset($cookies[$c[1]]);
            } else {
                $cookies[$c[1]] = $c[2];
            }
        }
    }
    return $r;
}

/** Escanea el carnet como el empleado: mete el código de tienda y abre el carnet, siguiendo redirecciones. */
function escanear(string $ruta, string $barcode, string $codigoTienda): array
{
    $cookies = [];
    peticion('POST', "/$ruta/index/carnetAttend?b=$barcode", $cookies, 'location_code=' . urlencode($codigoTienda));
    return seguir('GET', "/$ruta/index/carnetAttend?b=$barcode", $cookies);
}

/** Sigue las redirecciones desde una URL y devuelve la cadena de saltos, la respuesta final y las cookies. */
function seguir(string $metodo, string $url, array &$cookies): array
{
    $saltos = [];
    for ($i = 0; $i < 5; $i++) {
        $r = peticion($metodo, $url, $cookies);
        $saltos[] = ['status' => $r['status'], 'location' => $r['location']];
        if ($r['status'] < 300 || $r['status'] >= 400 || !$r['location']) {
            break;
        }
        $url = $r['location'];
        $metodo = 'GET';
    }
    return ['saltos' => $saltos, 'final' => $r, 'cookies' => $cookies];
}

/** Opciones del selector: [href, título, cuándo]. */
function opciones(string $html): array
{
    preg_match_all('#<a class="pick" href="([^"]+)">\s*<div class="meet">(.*?)</div>(?:\s*<div class="when">(.*?)</div>)?#s', $html, $m, PREG_SET_ORDER);
    return array_map(fn($x) => [
        html_entity_decode($x[1]),
        html_entity_decode($x[2]),
        html_entity_decode($x[3] ?? ''),
    ], $m);
}

/* ── Datos ── */
$hoy = date('Y-m-d');
$manana = date('Y-m-d', strtotime('+1 day'));
$codigos = $conn->fetchPairs('SELECT location_id, code FROM zaca_events_location WHERE location_id IN (' . TIENDA_A . ',' . TIENDA_B . ')');
$codigoA = (string) ($codigos[TIENDA_A] ?? '');
if ($codigoA === '') {
    echo "La tienda " . TIENDA_A . " no tiene código en dummy: no se puede probar.\n";
    exit(2);
}

// Un cliente con carnet cuyo color no comparta ningún otro cliente (el controlador coge el primero que coincide).
$cliente = $conn->fetchRow(
    "SELECT v.entity_id, UPPER(v.value) AS color FROM customer_entity_varchar v
     JOIN eav_attribute a ON a.attribute_id = v.attribute_id AND a.attribute_code = 'member_color_hex'
     JOIN eav_entity_type t ON t.entity_type_id = a.entity_type_id AND t.entity_type_code = 'customer'
     WHERE v.value REGEXP '^#[0-9A-Fa-f]{6}$'
       AND (SELECT COUNT(*) FROM customer_entity_varchar v2 WHERE v2.attribute_id = v.attribute_id AND UPPER(v2.value) = UPPER(v.value)) = 1
     ORDER BY v.entity_id DESC LIMIT 1"
);
if (!$cliente) {
    echo "Dummy no tiene ningún cliente con carnet (member_color_hex): no se puede probar.\n";
    exit(2);
}
$clienteId = (int) $cliente['entity_id'];
$barcode = 'CLT' . substr($cliente['color'], 1);
$ruta = $helper->getRoutePath();
echo "cliente=$clienteId carnet=$barcode tienda_A=" . TIENDA_A . " (código $codigoA) ruta_eventos=/$ruta hoy=$hoy\n";

$n = 0;
$crearReserva = function (int $tienda, string $fecha, string $estado = 'confirmed') use ($conn, $clienteId, &$n) {
    $conn->insert('zaca_events_table_booking', [
        'location_id' => $tienda, 'customer_id' => $clienteId, 'booking_date' => $fecha, 'status' => $estado,
        'phone_number' => '600000000', 'unsubscribe_code' => PREFIJO . (++$n) . '-' . bin2hex(random_bytes(6)),
    ]);
    $id = (int) $conn->lastInsertId();
    foreach ([3, 4] as $turno) { // 17:00-19:00 y 19:00-20:30 de la tienda 1
        $conn->insert('zaca_events_table_booking_slot', [
            'booking_id' => $id, 'location_id' => TIENDA_A, 'booking_date' => $fecha, 'time_slot_id' => $turno, 'tables_count' => 1,
        ]);
    }
    return $id;
};
$crearEvento = function (int $tienda, string $fecha) use ($conn, $clienteId, &$n) {
    $nombre = PREFIJO . ' Torneo de prueba ' . (++$n);
    $conn->insert('zaca_events_meet', [
        'name' => $nombre, 'location_id' => $tienda, 'meet_type' => 'casual', 'start_date' => "$fecha 20:15:00",
        'duration_minutes' => 120, 'max_slots' => 10, 'recurrence_type' => 'none', 'is_active' => 1,
    ]);
    $meetId = (int) $conn->lastInsertId();
    $conn->insert('zaca_events_registration', [
        'meet_id' => $meetId, 'customer_id' => $clienteId, 'status' => 'confirmed', 'phone_number' => '600000000',
        'registration_date' => date('Y-m-d H:i:s'), 'attendee_count' => 1, 'unsubscribe_code' => PREFIJO . $n . '-' . bin2hex(random_bytes(6)),
    ]);
    return ['nombre' => $nombre, 'registro' => (int) $conn->lastInsertId()];
};
$asistenciaReserva = fn(int $id) => (int) $conn->fetchOne(
    'SELECT COUNT(*) FROM zaca_events_attendance WHERE booking_id = ? AND registration_id IS NULL AND attendance_date = ?', [$id, $hoy]
);
$asistenciaRegistro = fn(int $id) => (int) $conn->fetchOne(
    'SELECT COUNT(*) FROM zaca_events_attendance WHERE registration_id = ? AND attendance_date = ?', [$id, $hoy]
);
// Que el cliente de prueba no traiga nada propio para hoy en la tienda A, o los resultados no se podrían atribuir.
$previo = (int) $conn->fetchOne(
    'SELECT (SELECT COUNT(*) FROM zaca_events_table_booking WHERE customer_id = ? AND location_id = ? AND booking_date = ?)
          + (SELECT COUNT(*) FROM zaca_events_registration r JOIN zaca_events_meet m ON m.meet_id = r.meet_id
              WHERE r.customer_id = ? AND m.location_id = ? AND DATE(m.start_date) = ?)',
    [$clienteId, TIENDA_A, $hoy, $clienteId, TIENDA_A, $hoy]
);
if ($previo > 0) {
    echo "El cliente $clienteId ya tiene $previo reserva(s)/inscripción(es) para hoy en la tienda A: se elige otro cliente.\n";
    exit(2);
}

$guardado = $conn->fetchAll($conn->select()->from($resource->getTableName('core_config_data'))->where('path = ?', INTERRUPTOR));
$restaurarInterruptor = function () use ($conn, $resource, $guardado, $typeList) {
    $tabla = $resource->getTableName('core_config_data');
    $conn->delete($tabla, ['path = ?' => INTERRUPTOR]);
    foreach ($guardado as $fila) {
        $conn->insert($tabla, ['scope' => $fila['scope'], 'scope_id' => $fila['scope_id'], 'path' => $fila['path'], 'value' => $fila['value']]);
    }
    foreach (['config', 'full_page', 'layout', 'block_html'] as $t) {
        $typeList->cleanType($t);
    }
};

$limpiar();
try {
    /* ── 1. Sólo reserva de hoy en la tienda ── */
    echo "\n== 1. Sólo reserva de ludoteca de hoy ==\n";
    $reserva = $crearReserva(TIENDA_A, $hoy);
    $r = escanear($ruta, $barcode, $codigoA);
    echo '  saltos: ' . json_encode($r['saltos']) . "\n";
    $esperado = "/ludoteca/attendance?id=$reserva&code=$codigoA";
    $check('1 el carnet redirige a la asistencia de la reserva', ($r['saltos'][0]['status'] === 302) && strpos((string) $r['saltos'][0]['location'], $esperado) !== false, $r['saltos'][0]['location']);
    $check('1 la página final dice «Reserva válida»', $r['final']['status'] === 200 && strpos($r['final']['body'], 'Reserva válida') !== false, $r['final']['status']);
    $check('1 la página final lista los turnos', strpos($r['final']['body'], '17:00') !== false && strpos($r['final']['body'], '19:00') !== false);
    $check('1 se escribe zaca_events_attendance con booking_id', $asistenciaReserva($reserva) === 1, $asistenciaReserva($reserva));
    $cookies = $r['cookies'];
    $r2 = seguir('GET', "/$ruta/index/carnetAttend?b=$barcode", $cookies);
    $check('1 al reescanear sale «Reserva ya registrada hoy»', strpos($r2['final']['body'], 'Reserva ya registrada hoy') !== false);
    $check('1 al reescanear no se duplica la asistencia', $asistenciaReserva($reserva) === 1);
    $limpiar();

    /* ── 2. Evento y reserva el mismo día ── */
    echo "\n== 2. Evento y reserva el mismo día ==\n";
    $reserva = $crearReserva(TIENDA_A, $hoy);
    $evento = $crearEvento(TIENDA_A, $hoy);
    $r = escanear($ruta, $barcode, $codigoA);
    echo '  saltos: ' . json_encode($r['saltos']) . "\n";
    $ops = opciones($r['final']['body']);
    echo '  selector: ' . json_encode($ops, JSON_UNESCAPED_UNICODE) . "\n";
    $check('2 sale el selector (200, sin redirección)', $r['final']['status'] === 200 && count($r['saltos']) === 1, $r['saltos']);
    $check('2 el selector tiene las dos opciones', count($ops) === 2, count($ops));
    $dellud = array_values(array_filter($ops, fn($o) => $o[1] === 'Ludoteca'));
    $deleve = array_values(array_filter($ops, fn($o) => $o[1] === $evento['nombre']));
    $check('2 la reserva sale como «Ludoteca» con sus turnos', count($dellud) === 1 && $dellud[0][2] === '17:00–19:00 · 19:00–20:30', $dellud[0][2] ?? null);
    $check('2 el evento sale con su nombre y hora', count($deleve) === 1 && $deleve[0][2] === 'Hoy a las 20:15', $deleve[0][2] ?? null);
    $check('2 el texto del selector menciona las reservas', strpos($r['final']['body'], 'eventos o reservas de ludoteca') !== false);
    if (count($dellud) === 1 && count($deleve) === 1) {
        $cookies = $r['cookies'];
        $a = seguir('GET', $dellud[0][0], $cookies);
        $check('2 la opción de la reserva dice «Reserva válida» y registra', strpos($a['final']['body'], 'Reserva válida') !== false && $asistenciaReserva($reserva) === 1, $asistenciaReserva($reserva));
        $b = seguir('GET', $deleve[0][0], $cookies);
        $check('2 la opción del evento registra la asistencia del evento', $b['final']['status'] === 200 && $asistenciaRegistro($evento['registro']) === 1, $asistenciaRegistro($evento['registro']));
    }
    $limpiar();

    /* ── 3. Lo que NO debe encontrar ── */
    echo "\n== 3. Reservas que no corresponden: mensaje de vacío ==\n";
    $casos = [
        '3 reserva de mañana' => fn() => $crearReserva(TIENDA_A, $manana),
        '3 reserva de hoy en otra tienda' => fn() => $crearReserva(TIENDA_B, $hoy),
        '3 reserva cancelada de hoy' => fn() => $crearReserva(TIENDA_A, $hoy, 'cancelled'),
    ];
    foreach ($casos as $etiqueta => $crear) {
        $reserva = $crear();
        $r = escanear($ruta, $barcode, $codigoA);
        $check("$etiqueta → vacío", $r['final']['status'] === 200 && count($r['saltos']) === 1 && strpos($r['final']['body'], TEXTO_VACIO) !== false, $r['saltos']);
        $check("$etiqueta → no se registra asistencia", $asistenciaReserva($reserva) === 0);
        $limpiar();
    }

    /* ── 4. Sólo evento: igual que antes ── */
    echo "\n== 4. Sólo evento de hoy ==\n";
    $evento = $crearEvento(TIENDA_A, $hoy);
    $r = escanear($ruta, $barcode, $codigoA);
    echo '  saltos: ' . json_encode($r['saltos']) . "\n";
    $check('4 redirige a la asistencia del evento', $r['saltos'][0]['status'] === 302 && strpos((string) $r['saltos'][0]['location'], "/index/attendance/registrationId/{$evento['registro']}") !== false, $r['saltos'][0]['location']);
    $check('4 se registra la asistencia del evento', $asistenciaRegistro($evento['registro']) === 1, $asistenciaRegistro($evento['registro']));
    $limpiar();

    /* ── 5. Ludoteca desactivada ── */
    echo "\n== 5. Ludoteca desactivada: no se buscan reservas ==\n";
    $writer->save(INTERRUPTOR, '0', 'default', 0);
    foreach (['config', 'full_page', 'layout', 'block_html'] as $t) {
        $typeList->cleanType($t);
    }
    $reserva = $crearReserva(TIENDA_A, $hoy);
    $r = escanear($ruta, $barcode, $codigoA);
    $check('5 sólo reserva → vacío', $r['final']['status'] === 200 && count($r['saltos']) === 1 && strpos($r['final']['body'], TEXTO_VACIO) !== false, $r['saltos']);
    $check('5 sólo reserva → no se registra asistencia', $asistenciaReserva($reserva) === 0);
    $evento = $crearEvento(TIENDA_A, $hoy);
    $r = escanear($ruta, $barcode, $codigoA);
    $check('5 evento y reserva → redirige al evento (sin selector)', $r['saltos'][0]['status'] === 302 && strpos((string) $r['saltos'][0]['location'], "/index/attendance/registrationId/{$evento['registro']}") !== false, $r['saltos'][0]['location']);
    $limpiar();
} finally {
    $restaurarInterruptor();
    echo "\n(interruptor de ludoteca devuelto a como estaba: " . json_encode(array_map(fn($f) => [$f['scope'], $f['scope_id'], $f['value']], $guardado)) . ")\n";
    if (isset($opts['conservar'])) {
        echo "CONSERVADOS los datos de prueba (prefijo " . PREFIJO . ")\n";
    } else {
        $limpiar();
    }
}

echo "\nRESULTADO: $pass PASS, $fail FAIL\n";
exit($fail === 0 ? 0 : 1);
