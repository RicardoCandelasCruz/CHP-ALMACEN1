<?php
// Configuración de la base de datos PostgreSQL
define('DB_HOST', 'aws-pedidosdb1.c7igma8qqvx8.us-east-2.rds.amazonaws.com');
define('DB_PORT', '5432');
define('DB_NAME', 'pedidosdb');
define('DB_USER', 'postgres');
define('DB_PASS', 'cheesepizza2001');
define('DB_CHARSET', 'utf8');

// Configuración de la aplicación
define('APP_DEBUG', true);
define('SESSION_TIMEOUT', 1800);

// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'sistemacheesepizza@gmail.com');
define('SMTP_PASS', 'opkj posq xeht qqvw');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'cheesepizzarecepcion@gmail.com');
define('ADMIN_EMAIL', 'sistemacheesepizza@gmail.com');

?>