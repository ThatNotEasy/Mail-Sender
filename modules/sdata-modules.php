<?php
error_reporting(0);

class Sdata
{
    // Set proxy rules
    public function setRules($rules = null) {
        if (file_exists($rules['proxy']['file'])) {
            $this->setNewProxy();
        }

        return $this->proxy_rules = $rules;
    }

    // Set a new proxy by selecting from the file and checking against a blacklist
    public function setNewProxy() {
        $blacklist = file_get_contents("proxy-blacklist.txt");
        $file = file_get_contents($this->proxy_rules['proxy']['file']);
        $file = explode("\r\n", $file);

        for ($i = 0; $i < 12; $i++) { 
            $proxy = $file[array_rand($file)];
            if (preg_match("/" . $proxy . "/", $blacklist)) {
                break;
            }
            $i++;
        }
        
        if ($proxy) {
            file_put_contents("proxy-use.txt", $proxy);
        }
    }

    // Retrieve the current proxy being used
    public function setProxy() {
        $file = file_get_contents("proxy-use.txt");
        $file = explode(":", $file);

        if (count($file) == 4) {
            return array(
                'ip'       => $file[0],
                'port'     => $file[1],
                'username' => $file[2],
                'password' => $file[3],
            );
        } else {
            return array(
                'ip'   => $file[0],
                'port' => $file[1],
            );
        }
    }

    // Perform concurrent HTTP requests
    public function sdata($url = null, $custom = null) {
        mkdir('cookies'); // Please don't remove
        $ch = array();
        $mh = curl_multi_init();
        $total = count($url);
        $allResponses = array();

        for ($i = 0; $i < $total; $i++) {
            if ($url[$i]['cookies']) {
                $cookies = $url[$i]['cookies'];
            } else {
                $cookies = 'cookies/shc-' . md5($this->cookies()) . "-" . time() . '.txt'; 
            }

            $ch[$i] = curl_init();
            $threads[$ch[$i]] = array(
                'process_id' => $i,
                'url'        => $url[$i]['url'],
                'cookies'    => $cookies,
                'note'       => $url[$i]['note'],
            );

            curl_setopt($ch[$i], CURLOPT_URL, $url[$i]['url']);
            if ($custom[$i]['gzip']) {
                curl_setopt($ch[$i], CURLOPT_ENCODING, "gzip");
            }
            curl_setopt($ch[$i], CURLOPT_HEADER, false);
            curl_setopt($ch[$i], CURLOPT_COOKIEJAR, $cookies);
            curl_setopt($ch[$i], CURLOPT_COOKIEFILE, $cookies);

            if ($custom[$i]['rto']) {
                curl_setopt($ch[$i], CURLOPT_TIMEOUT, $custom[$i]['rto']);
            } else {
                curl_setopt($ch[$i], CURLOPT_TIMEOUT, 60);
            }

            if ($custom[$i]['header']) {
                curl_setopt($ch[$i], CURLOPT_HTTPHEADER, $custom[$i]['header']);
            }

            if ($custom[$i]['post']) {
                $query = is_array($custom[$i]['post']) ? http_build_query($custom[$i]['post']) : $custom[$i]['post'];
                curl_setopt($ch[$i], CURLOPT_POST, true);
                curl_setopt($ch[$i], CURLOPT_POSTFIELDS, $query);
            }

            if ($custom[$i]['proxy']) {
                if ($custom[$i]['proxy']['type']) {
                    curl_setopt($ch[$i], CURLOPT_PROXY, $custom[$i]['proxy']['ip']);
                    curl_setopt($ch[$i], CURLOPT_PROXYPORT, $custom[$i]['proxy']['port']);
                    curl_setopt($ch[$i], CURLOPT_PROXYTYPE, $custom[$i]['proxy']['type']);
                }
            }

            if ($this->proxy_rules) {
                $proxy = $this->setProxy();
                curl_setopt($ch[$i], CURLOPT_PROXY, $proxy['ip']);
                curl_setopt($ch[$i], CURLOPT_PROXYPORT, $proxy['port']);
                if ($proxy['username']) {
                    curl_setopt($ch[$i], CURLOPT_PROXYUSERPWD, $proxy['username'] . ":" . $proxy['password']);
                }
            }

            if ($this->proxy_rules['proxy']['auth']) {
                curl_setopt($ch[$i], CURLOPT_PROXY, $this->proxy_rules['proxy']['auth']['hostname'] . ':' . $this->proxy_rules['proxy']['auth']['port']);
                curl_setopt($ch[$i], CURLOPT_PROXYUSERPWD, $this->proxy_rules['proxy']['auth']['username'] . ':' . $this->proxy_rules['proxy']['auth']['password']);
            }

            curl_setopt($ch[$i], CURLOPT_VERBOSE, false);
            curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch[$i], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch[$i], CURLOPT_SSL_VERIFYHOST, false);

            if ($custom[$i]['uagent']) {
                curl_setopt($ch[$i], CURLOPT_USERAGENT, $custom[$i]['uagent']);
            } else {
                curl_setopt($ch[$i], CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU iPhone OS 8_3 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) CriOS/42.0.2311.47 Mobile/12F70 Safari/600.1.4");
            }

            curl_multi_add_handle($mh, $ch[$i]);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
            while ($info = curl_multi_info_read($mh)) {    
                $threads_data = $threads[$info['handle']];
                $result = curl_multi_getcontent($info['handle']);
                $info = curl_getinfo($info['handle']);

                $allResponses[] = array(
                    'id'     => $threads_data['process_id'],
                    'data'   => $threads_data,
                    'response' => $result,
                    'info'   => array(
                        'full'      => $info,
                        'time'      => str_replace('E-', '', $info['connect_time']),
                        'url'       => $info['url'],
                        'http_code' => $info['http_code'],
                    ),
                );
                curl_multi_remove_handle($mh, $info['handle']);
            }
            usleep(100);
        } while ($active);
        curl_multi_close($mh);

        usort($allResponses, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });

        return $allResponses;
    }

    // Generate a random cookie string
    public function cookies($length = 60) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString . time() . rand(10000000, 99999999);
    }

    // Remove session cookies
    public function session_remove($arrayResponses) {
        foreach ($arrayResponses as $key => $value) {
            unlink($value['data']['cookies']);
        }
    }

    // Sort an array by a specific key
    public function aasort(&$array, $key) {
        $sorter = array();
        $ret = array();

        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }
}

$sdata = new Sdata();
