# Zacatrus Events Module

Módulo de gestión de eventos para Magento 2.

## Instalación

### Opción 1: Instalación manual

1. Copia el contenido de este directorio a `app/code/Zaca/Events/` en tu instalación de Magento
2. Ejecuta:
```bash
bin/magento module:enable Zaca_Events
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Opción 2: Instalación vía Composer

1. Agrega el repositorio a tu `composer.json`:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/home/sergio/dev/magento/events"
        }
    ]
}
```

2. Instala el módulo:
```bash
composer require zaca/events:*
bin/magento module:enable Zaca_Events
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Estructura

- `Api/` - Interfaces de API REST
- `Block/` - Bloques para frontend
- `Controller/` - Controladores (admin y frontend)
- `Model/` - Modelos de datos y repositorios
- `etc/` - Configuración del módulo
- `view/` - Plantillas, layouts y assets frontend
- `Ui/` - Componentes UI para admin

## Uso

### Frontend
- Lista de eventos: `/events/`
- Inscripción: Se realiza mediante AJAX desde la lista de eventos

### Backend
- Accede desde el menú: **Zacatrus Events** > **Stores/Events/Leagues/Registrations**

## Requisitos

- Magento 2.4.x
- PHP 8.0+

