---
paths:
  - 'app/Models/User.php,app/Modules/Admin/**,app/Modules/Personnel/Models/Employee.php,resources/js/Pages/Settings/Users/**,database/migrations/*users*'
---

# Migrations

## Las cuentas de usuario pertenecen a colaboradores
Cada cuenta de usuario se vincula uno a uno con un colaborador mediante employee_id. Nombre, correo, teléfono y rol provienen de Personal; no se reasigna el rol en Usuarios. Usuarios captura username, contraseña y PIN de 4 dígitos, y contraseña/PIN deben almacenarse con hash.
