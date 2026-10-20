<?php
/**
 * PRUEBA EN DUMMY: el export CSV de participantes no da avisos de PHP (8.4 avisa de fputcsv sin $escape) y
 * produce el mismo CSV de siempre.
 *   sudo -u www-data php vendor/zaca/events/Test/dummy/compat_php_export_csv.php
 *
 * Usa la clase REAL del módulo ya desplegada (con el padre real de Magento), sin tocar la base de datos: llama a
 * arrayToCsv() con filas con comas, comillas, barra invertida y acentos y compara con el resultado esperado. Los
 * avisos de PHP se recogen con E_ALL; cualquiera hace fallar la prueba.
 */
use Magento\Framework\App\Bootstrap;

require is_file(__DIR__ . '/../../../../../app/bootstrap.php')
    ? __DIR__ . '/../../../../../app/bootstrap.php'
    : '/var/www/zacatrus/current/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$bootstrap->getObjectManager();

error_reporting(E_ALL);
$avisos = [];
set_error_handler(function ($no, $msg) use (&$avisos) {
    if (!(error_reporting() & $no)) {
        return false;
    }
    $avisos[] = "($no) $msg";
    return true;
});

$clase = new \ReflectionClass(\Zaca\Events\Controller\Adminhtml\Participant\ExportCsv::class);
$controlador = $clase->newInstanceWithoutConstructor();
$metodo = $clase->getMethod('arrayToCsv');
if (PHP_VERSION_ID < 80100) {
    $metodo->setAccessible(true);
}

$casos = [
    'normal' => [
        [12, 'Ana García', 'ana@zacatrus.es', '600123456', 'Liga "Catan", ronda 2', 5, 77, 'confirmed', 1, '2026-10-20', '2026-10-01 10:00:00'],
        '12,"Ana García",ana@zacatrus.es,600123456,"Liga ""Catan"", ronda 2",5,77,confirmed,1,2026-10-20,"2026-10-01 10:00:00"',
    ],
    'barra invertida' => [
        [13, 'O\'Neil \\ Barra', 'x@infovit.net', '', 'Torneo\\"raro"', 5, 78, 'waitlist', 0, '2026-10-21', '2026-10-02 11:00:00'],
        '13,"O\'Neil \\ Barra",x@infovit.net,,"Torneo\\"raro""",5,78,waitlist,0,2026-10-21,"2026-10-02 11:00:00"',
    ],
];

$fallos = 0;
foreach ($casos as $nombre => [$fila, $esperado]) {
    $obtenido = $metodo->invoke($controlador, $fila);
    $ok = $obtenido === $esperado;
    echo ($ok ? 'OK    ' : 'FALLO ') . "$nombre: $obtenido\n";
    if (!$ok) {
        echo "      esperado: $esperado\n";
        $fallos++;
    }
}
foreach ($avisos as $aviso) {
    echo "FALLO aviso de PHP: $aviso\n";
    $fallos++;
}
echo 'PHP ' . PHP_VERSION . ' | avisos: ' . count($avisos) . ' | fallos: ' . $fallos . "\n";
exit($fallos === 0 ? 0 : 1);
