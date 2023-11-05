<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */
 
require_once ($mgmt_config['abs_path_cms']."library/phpmailer/class.phpmailer.php");

class HyperMailer extends PHPMailer
{
  public function __construct()
  {
    global $mgmt_config, $hcms_lang_codepage, $lang;

    if (empty ($lang)) $lang = "en";

    $this->IsSMTP();
    $this->SMTPAuth = true;
    if (!empty ($hcms_lang_codepage[$lang])) $this->CharSet = $hcms_lang_codepage[$lang];
    if (!empty ($mgmt_config['smtp_host'])) $this->Host = $mgmt_config['smtp_host'];
    if (!empty ($mgmt_config['smtp_username'])) $this->Username = $mgmt_config['smtp_username'];
    if (!empty ($mgmt_config['smtp_password'])) $this->Password = $mgmt_config['smtp_password'];
    if (!empty ($mgmt_config['smtp_port'])) $this->Port = $mgmt_config['smtp_port'];
    if (!empty ($mgmt_config['smtp_sender'])) $this->Sender = $mgmt_config['smtp_sender'];
  }

  // for backwards compatibility
  public function HyperMailer()
  {
    self::__construct();
  }
} 
