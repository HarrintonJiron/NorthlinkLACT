# Operación de producción · Northlink Quebar

Este procedimiento se ejecuta solamente cuando la versión candidata ha pasado pruebas PostgreSQL, compilación web y validación de contenedores. Nunca se restaura sobre producción sin autorización y respaldo verificado.

## Preparación

1. Copiar `.env.vps.example` como `.env.vps` fuera del repositorio y reemplazar todas las claves.
2. Generar `APP_KEY` una sola vez y custodiarla; cambiarla invalida sesiones y datos cifrados.
3. Confirmar espacio libre, acceso HTTPS, estado de PostgreSQL y Redis.
4. Crear un respaldo con `scripts/backup-production.sh` y comprobar que `pg_restore --list` lo puede leer.
5. Registrar la etiqueta o hash de la imagen actualmente estable para rollback.

## Despliegue controlado

1. Construir la imagen candidata sin reutilizar una etiqueta estable.
2. Ejecutar las pruebas automáticas contra PostgreSQL.
3. Iniciar PostgreSQL y Redis; luego la aplicación, worker y scheduler.
4. El rol `web` aplica migraciones con `--force`; worker y scheduler nunca migran.
5. En la primera versión con archivos privados de RR. HH., ejecutar `php artisan northlink:personnel-files-private` y detener el despliegue si reporta referencias faltantes.
6. Esperar el estado saludable de `/up` y probar inicio de sesión, dashboard, inventario, finanzas, productores, producción, PDF y Excel.
7. Verificar que el worker procese colas y que exista un solo scheduler activo.

## Observación

- Vigilar salud HTTP, reinicios de contenedores, uso de disco, memoria, errores 5xx y trabajos fallidos.
- Revisar diariamente la creación de respaldos; la retención automática es de 14 días.
- Probar una restauración en una base aislada al menos una vez al mes.

## Rollback

1. Detener el ingreso de datos o activar mantenimiento.
2. Volver a la imagen estable registrada.
3. Si una migración incompatible modificó datos, restaurar el respaldo previo en una base aislada, validarlo y solo entonces autorizar el cambio de base.
4. Levantar servicios, comprobar `/up` y ejecutar las pruebas funcionales esenciales.
5. Documentar causa, impacto, tiempos y acciones correctivas.

La restauración requiere `CONFIRM_RESTORE=RESTORE_NORTHLINK` para evitar ejecuciones accidentales.
