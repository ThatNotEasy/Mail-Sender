<?php


class SendinboxMailer
{
    public function __construct($config)
    {
        $this->config = $config;
        $this->installPackage();
    }
    public function installPackage(){
        mkdir(SENDINBOX_PATH.'/Emailist');
        mkdir(SENDINBOX_PATH.'/Letter');
    }
    public function version(){
        return array(
            'name'      => 'Sendinbox',
            'version'   => 'Mailers', 
            'issue'     => '1.1',
            'codename'  => false,
        );
    }
    public function prompt($msg){
        echo $this->color("green","[Sendinbox] ").$this->color("purple",$msg);
        $answer =  rtrim( fgets( STDIN ));
        return $answer;
    }
    public function cover(){
        $template[0] .= $this->color("random" , "==================================================\r\n");
        $template[0] .= $this->color("random" , "      _______    || ".$this->color('string' , $this->version()['name'])." ".$this->version()['version']." (issue ".$this->version()['issue'].")\r\n");
        $template[0] .= $this->color("random" , "     |==   []|   || (c) ".date(Y)." ".$this->color("random","emailist").".org\r\n");
        $template[0] .= $this->color("random" , "     '-------'   || it's full of great features!\r\n");
        $template[0] .= $this->color("random" , "==================================================\r\n");
        print_r($template[0]);
        echo "\r\n";
    }
    public function required(){
        echo $this->color("green","[Sendinbox] ".$this->color('bggreen', "Searching for email list files\r\n"));
        $locdir_list    = SENDINBOX_PATH.'/Emailist';
        $list_load      = scandir($locdir_list);
        foreach ($list_load as $key => $value) {
            if(is_file($locdir_list."/".$value)){
                $arrayList[] = $locdir_list."/".$value;
            }
        }
        if(count($arrayList) == 0){
            echo $this->color("green","[Sendinbox] ".$this->color('bgred', "Please place email list files in the Emailist folder\r\n"));
            echo $this->color("green","[Sendinbox] ".$this->color('bgred', "No email list files found in the Emailist folder\r\n"));
            die();
        }
        echo $this->color("green","[Sendinbox] ".$this->color('bggreen', "Found ".count($arrayList)." email list files.")."\r\n\n");
        echo $this->color("green","====================================\r\n");
        foreach ($arrayList as $key => $value) {
            echo $this->color("nevy","[Emailist] [$key] ".pathinfo($value)[filename]."\r\n");
        }
        echo $this->color("green","====================================\r\n");
        echo "\r\n";
        $choice = $this->prompt("Enter the list number: ");
        $fgt = file_get_contents($arrayList[$choice]);
        if(empty($fgt)){
            echo $this->color("red","[Sendinbox] Your choice is incorrect!!!\r\n");
            die();
        }
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $fgt = explode("\r\n", $fgt);
        } else {
            $fgt = explode("\n", $fgt);
        }

        echo $this->color("green","[Sendinbox] There are ".$this->color('red',count($fgt))." emails in the list.\r\n\n");
        $choice = $this->prompt("Remove duplicate emails? 0 = No , 1 = Yes: ");
        if($choice == 1){
            $fgt = array_unique($fgt);
        }
        return $fgt;
    }
    public function dot($text , $default = 28){
        if(strlen($text) <= $default){
            for ($i=0; $i <($default-strlen($text)); $i++) { 
                $dot.= " ";
            }
        }
        return substr($text , 0,$default).$dot;
    }
    public function extract_message($message = ""){
        foreach ($message as $key => $value) {
            foreach ($value as $key => $respons) {
                preg_match_all('/=\?utf-8\?B\?(.*?)\?=/m', $respons['json']['subject'], $sb);
                $message_output .= $this->color('green' , '[Sendinbox]');
                $message_output .= '['.$this->color('yellow' , ($respons['line']+1)).'] ';
                $message_output .= $this->color('nevy' , $this->dot(trim($respons['email'])) )." | ";
                $message_output .= $this->color('yellow' , $this->dot(trim(base64_decode($sb[1][0])),19) )." | ";
                $message_output .= ($respons['json']['status'] == true ? $this->color('green',$respons['json']['message']):$this->color('red',$respons['json']['message']))." | ";
                $message_output .= $this->color('nevy' , $respons['json']['note']['server'])." (".($respons['code'] == '200' ? $this->color('green',$respons['code']):$this->color('red',$respons['code'])).")";
                echo $message_output."\r\n";
                unset($message_output);

            }
        }
    }
    public function alias($data  , $email = "" , $encryp = false){
        $data   = str_replace("{email}", $email , $data);
        $data   = str_replace("{date}", date("F j, Y, g:i a") , $data);
        $data   = str_replace("{ip}", rand(10,999).".".rand(10,999).".".rand(10,999).".".rand(10,999) , $data);
        $data   = str_replace("{country}", strtoupper($this->country()['value']) , $data);
        $data   = str_replace("{device}", strtoupper($this->device()['value']) , $data);
        $data   = str_replace("{browser}", $this->browser()['value'] , $data);
        $data   = $this->check_random($data , 'low'); // 'up' = for uppercase random text, 'low' = for lowercase
        if( $encryp == true){
            foreach ($config['encrypt_terms'] as $key => $encrypted_word) {
                $data   = str_replace($encrypted_word, $this->enc_letter($encrypted_word), $data);
            }
        }
        return $data; 
    }
    public function arrayrandom($array){
        $random = array_rand($array);
        return array(
            'value' => $array[$random], 
            'key'   => $random
        );
    }
    public function browser(){
        $browser = array('Mozilla Firefox' , 'Chrome' , 'Safari');
        return $this->arrayrandom($browser);
    }
    public function device(){
        $device = array(
            'iPhone 6S Plus','iPhone 6S','iPhone SE','iPad Pro 9.7','iPhone 7 Plus',
            'iPhone 7','IPad Pro','IPhone 8','IPhone 8+','IPhone 7+','Iphone X'
        );
        return $this->arrayrandom($device);
    }
    public function country(){
        $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
        return $this->arrayrandom($countries);
    }
    public function check_random($data , $options){ 
            preg_match_all('/{(.*?)}/', $data, $matches);
            foreach ($matches[1] as $key => $value) {
                $explode    = explode(",", $value);
                $type       = $explode[0];
                $length     = $explode[1];
                if($explode[3]){
                    $options    = $explode[3];
                }
                $random     = $this->random_text($type , $length , $options);
                $data       = str_replace($value, $random, $data);
            }
            return str_replace("{", "", str_replace("}", "", $data));
        }
    public function random_text($type , $length = 10 , $lowup = 'up'){
         switch ($type) {
            case 'textrandom':
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
            case 'numrandom':
                $characters = '0123456789';
            break;
            case 'textnumrandom':
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
            
            default:
                $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        }
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        switch ( strtolower($lowup) ) { 
            case 'low':
                $randomString = strtolower( $randomString );
            break;
            case 'up':
                $randomString = strtoupper( $randomString );
            break;
            
            default:
                $randomString = strtolower( $randomString );
            break;
        }
        return $randomString;
    }
    public function enc_letter($text){
        foreach (str_split($text) as $key => $value) {
          $fText .= $value."<font style='color:transparent;font-size:0px'>".rand(100,9999)."<!--".rand(100,9999)."--></font>"."<!-- ".md5($text.md5(rand(10,999999)))."-->";
        }
        return $fText;
    }
    public function color($color = "random" , $text){
        if($this->config['config']['color'] == true){
            $arrayColor = array(
                'grey'      => '1;30',
                'red'       => '1;31',
                'green'     => '1;32',
                'yellow'    => '1;33',
                'blue'      => '1;34',
                'purple'    => '1;35',
                'nevy'      => '1;36',
                'white'     => '1;1',
                'bgred'     => '1;41',
                'bggreen'   => '1;42',
                'bgyellow'  => '1;43',
                'bgblue'    => '1;44',
                'bgpurple'  => '1;45',
                'bgnavy'    => '1;46',
                'bgwhite'   => '1;47',
            );  
            if($color == 'random'){
                $arrayColor = array(
                    'red'       => '1;31',
                    'green'     => '1;32',
                    'yellow'    => '1;33',
                    'nevy'      => '1;36',
                    'white'     => '1;1',
                );  
                $arrayColor[$color] = $arrayColor[array_rand($arrayColor)];
                $res .=  "\033[".$arrayColor[$color]."m".$text."\033[0m";

            }else if($color == 'string'){
                $arrayColor = array(
                    'grey'      => '1;30',
                    'red'       => '1;31',
                    'green'     => '1;32',
                    'yellow'    => '1;33',
                    'blue'      => '1;34',
                    'purple'    => '1;35',
                    'nevy'      => '1;36',
                    'white'     => '1;1',
                );  
                foreach (str_split($text) as $key => $value) {
                    $arrayColor[$color] = $arrayColor[array_rand($arrayColor)];
                    $res .= "\033[".$arrayColor[$color]."m".$value."\033[0m";
                }

            }else{
                
                $res .=  "\033[".$arrayColor[$color]."m".$text."\033[0m";
            
            }
            return $res;
        }else{
            return $text;
        }
        
    }
}
