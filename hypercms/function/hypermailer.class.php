<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */
 
include ($mgmt_config['abs_path_cms']."library/phpmailer/class.phpmailer.php");

class HyperMailer extends PHPMailer
{  
  public function __construct()
  {
    global $mgmt_config, $hcms_lang_codepage, $lang;

    $this->IsSMTP();
    $this->SMTPAuth = true;
    $this->CharSet = $hcms_lang_codepage[$lang];
    
    $this->Host     = $mgmt_config['smtp_host'];
    $this->Username = $mgmt_config['smtp_username'];
    $this->Password = $mgmt_config['smtp_password'];
    $this->Port     = $mgmt_config['smtp_port'];
    $this->Sender   = $mgmt_config['smtp_sender'];
  }
  
  // for backwards compatibility
  public function HyperMailer()
  {
    self::__construct();
  }
} 
