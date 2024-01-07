<?php


define('CLI_SCRIPT', 1);

require_once dirname(__FILE__)."/../../config.php";

\report_trainingenrolment\helper\adhoc_report_data_helper::download_postcode_excel();
