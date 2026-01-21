<?php
error_reporting(E_ALL & ~E_DEPRECATED);

require_once('lib/PHPExcel/PHPExcel.php');

// Incluir archivos de PHPMailer
require_once('lib/PHPMailer/src/Exception.php');
require_once('lib/PHPMailer/src/PHPMailer.php');
require_once('lib/PHPMailer/src/SMTP.php');
require_once('functions/dbconvertfieldtype.php');