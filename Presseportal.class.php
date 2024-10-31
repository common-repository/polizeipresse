<?php

class PresseportalResponse {

    public $api_result;
    public $data;
    public $http_status;
    public $request_url;
    public $format;
    public $error;
    public $error_code;
    public $error_msg;

    private function check_error() {
        if($this->http_status != "200") {
            $this->error =  true;
        }
    }

    public function parse() {
        $this->check_error();
        if($this->error) $this->{'parse_error_'.$this->format}();
        else $this->{'parse_content_'.$this->format}();
    }

    function __get($prop_name) {
        switch($prop_name) {
            case 'success':
                $this->succes =  $this->data->success;
                return $this->info;
                break;
            case 'stories':
                $this->stories = (isset($this->data->content->story)) ? $this->data->content->story : false;
                return $this->stories;
                break;
            case 'request':
            case 'info':
                $this->{$prop_name} = (isset($this->data->request)) ? $this->data->request : false;
                return $this->{$prop_name};
                break;
            case 'company':
            case 'office':
            case 'request':
                $this->{$prop_name} = (isset($this->data->{$prop_name})) ? $this->data->{$prop_name} : false;
                return $this->{$prop_name};
                break;
            case 'media':
                $this->{$prop_name} = false;
                if(isset($this->data->{$prop_name})) {
                    $type = key($this->data->{$prop_name});
                    $this->{$prop_name} = $this->data->{$prop_name};
                }
                return $this->{$prop_name};
                break;
            case 'hits':
                $this->{$prop_name} = (isset($this->data->content->result)) ? $this->data->content->result : false;
                return $this->{$prop_name};
                break;
            case 'offices':
                $this->offices = (isset($this->data->content->result)) ? $this->data->content->result : false;
                return $this->offices;
                break;
            default:
                throw new Exception('Property '.$prop_name.' now known!');
        }
    }

    protected function parse_error_json() {
        if($result = json_decode($this->api_result)) {
            $this->error_code = $result->error->code;
            $this->error_msg = $result->error->msg;
        }
    }

    protected function parse_content_json() {
        $this->data = json_decode($this->api_result);
    }

    protected function parse_error_xml() {
        if($xml = simplexml_load_string($this->api_result)) {
            $this->error_code = (int) $xml->error->code;
            $this->error_msg = (string) $xml->error->msg;
        }
    }

    protected function parse_content_xml() {
        $this->data = $this->xml_to_object($this->api_result);
    }

    protected function xml_to_object($xml) {
        if(is_string($xml)) $xml = new SimpleXMLElement( $xml );
        $children = $xml->children();
        if ( !$children ) return (string) $xml;
        $arr = new StdClass();
        foreach ( $children as $key => $node ) {
            $node = $this->xml_to_object( $node );

            if(isset($arr->{$key})) {
                if(!is_array($arr->{$key})||$arr->{$key}[0]==null) {
                    $arr->{$key} = array($arr->{$key});
                }
                $arr->{$key}[] = $node;
            } elseif(in_array($key, array('image','story','result','keyword','document','video','link'))) {
                $arr->{$key}[] = $node;
            } else {
                $arr->{$key} = $node;
            }
        }
        return $arr;
    }

}

class Presseportal {

    public $user_agent = 'Presseportal Client v0.2';
    public $timeout_connect = 10;
    public $timeout_transfer = 20;
    public $format = 'json';
    public $start = 0;
    public $limit = 20;
    public $teaser;
    public $term;

    protected $api_key;
    protected $api_host_de = 'api.presseportal.de';
    protected $api_host_ch = 'api.presseportal.ch';
    protected $api_host;
    protected $api_url;
    protected $portal;
    protected $lang_id;
    protected $proxy_host;
    protected $proxy_port;


    public function __construct($api_key, $portal="de", $lang='de') {
        $this->api_key = $api_key;
        $this->portal = $portal;
        $this->lang = $lang;
        $this->set_api_host();
        $this->set_api_url();
    }

    protected function set_api_host() {
        $this->api_host = $this->{'api_host_'.$this->portal};
    }

    protected function set_api_url() {
        $this->api_url = 'http://'.$this->api_host.'/api';
    }

    protected function build_request_url($ressource,$params=array()) {
        foreach($params AS $k => $v) {
            if($v) $this->{$k} = $v;
        }

        $url = $this->api_url.$ressource.'?api_key='.$this->api_key;
        $url .= '&format='.$this->format;
        if($this->start > 0) $url .= '&start='.$this->start;
        if($this->limit > 0) $url .= '&limit='.$this->limit;
        if($this->lang != 'de' or $this->portal == "ch") {
            $url .= '&lang='.$this->lang;
        }
        if($this->teaser) $url .= '&teaser=1';
        if($this->term) $url .= '&q='.urlencode($this->term);

        return $url;
    }

    protected function build_ressource_uri($controller, $method, $id=false, $extra_1=false, $extra_2=false) {
        $uri = '/'.$controller.'/'.$method;
        if($id) $uri .= '/'.urlencode($id);
        if($extra_1) $uri .= '/'.urlencode($extra_1);
        if($extra_2) $uri .= '/'.urlencode($extra_2);
        return $uri;
    }

    public function set_proxy($host, $port) {
        $this->proxy_host = $host;
        $this->proxy_port = $port;
    }

    public function get_company_info($company_id) {
        $ressource = $this->build_ressource_uri('info', 'company', $company_id);
        return $this->do_request($ressource);
    }

    public function get_office_info($office_id) {
        $ressource = $this->build_ressource_uri('info', 'office', $office_id);
        return $this->do_request($ressource);
    }

    public function get_media_info($media_id) {
        $ressource = $this->build_ressource_uri('info', 'media', $media_id);
        return $this->do_request($ressource);
    }

    public function get_articles($media=false, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('article', 'all', null, $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_ressort_articles($ident, $media=false, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('article', 'ressort', $ident, $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_branche_articles($ident, $media=false, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('article', 'branche', $ident, $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_keyword_articles($ident, $media=false, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('article', 'keyword', $ident, $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_company_articles($ident, $media=false, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('article', 'company', $ident, $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_publicservice_articles($region=false, $media=false, $start=false, $limit=false, $teaser=false) {
        if($region) $ressource = $this->build_ressource_uri('article', 'publicservice', 'region', $region, $media);
        else $ressource = $this->build_ressource_uri('article', 'publicservice', $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_office_articles($id, $media=false, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('article', 'publicservice', 'office', $id, $media);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_ir_articles($ident='all', $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('ir', $ident);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function get_ir_company_articles($ident, $company_id, $start=false, $limit=false, $teaser=false) {
        $ressource = $this->build_ressource_uri('ir', 'company', $company_id, $ident);
        return $this->do_request($ressource, array('start'=>$start, 'limit'=>$limit, 'teaser'=>$teaser));
    }

    public function search_company($term, $limit=false) {
        $ressource = $this->build_ressource_uri('search', 'company');
        return $this->do_request($ressource, array('limit'=>$limit, 'term' => $term));
    }

    public function search_office($term, $limit=false) {
        $ressource = $this->build_ressource_uri('search', 'office');
        return $this->do_request($ressource, array('limit'=>$limit, 'term' => $term));
    }

    protected function do_request($ressource, $params=array()) {
        $request_url = $this->build_request_url($ressource, $params);
        return $this->api_request($request_url);
    }

    protected function api_request($req_url) {

        $response = new PresseportalResponse();
        $response->request_url = $req_url;
        $response->limit = $this->limit;
        $response->format = $this->format;
        if (function_exists('curl_init')) {
            // initiate curl
            $ch = curl_init($req_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout_connect);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout_transfer);
            if($this->proxy_host && $this->proxy_port) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy_host);
                curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
            }

            // ask the api
            if ($response->api_result = curl_exec($ch)) {
                $response->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }
            curl_close($ch);
        } else {
            // user agent
            ini_set('user_agent', $this->user_agent);

            if($this->proxy_host && $this->proxy_port) {
                $url = $host = $this->proxy_host;
                $port = $this->proxy_port;
                $http_path = $req_url;
            } else {
                $url = $req_url;
                $host = $this->api_host;
                $port = 80;
                $http_path = $req_url;
            }

            $fp = fsockopen(parse_url($url, PHP_URL_HOST), $port, $errno, $errstr, $this->timeout_transfer);
            if (!$fp) {
                echo "$errstr ($errno)<br />\n";
            } else {
                $out = "GET ".$http_path." HTTP/1.0\r\n";
                $out .= "Host: ".$host."\r\n";
                $out .= "Connection: Close\r\n\r\n";
                fwrite($fp, $out);
                $resp = '';
                while (!feof($fp)) {
                    $resp .= fgets($fp, 128);
                }
                fclose($fp);
                // get content
				//print("<p>&nbsp;</p>");
				//print("<p>&nbsp;</p>");
				//print("<p> Response: " . $resp . "</p>");
                $response->api_result = trim(strrchr($resp, "\r\n\r\n"));
                // get http status
                preg_match('/^HTTP.* ([0-9]{3})/iU', $resp, $preg_res);
                $response->http_status =  $preg_res[1];
            }
        }
        $response->parse();
        return $response;
    }

}
?>