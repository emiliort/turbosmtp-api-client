<?php
/**
 * turboSMTP API v2 Client for PHP 5.2
 */
class TurboSmtpClient {
    private $authuser;
    private $authpass;
    private $server;
    private $endPoint; 

    /**
     * Undocumented function
     *
     * @param string $authuser authuser is the email of turboSMTP account (this param can be used as POST or Headers)
     * @param string $authpass authpass is the password of turboSMTP account (this param can be used as POST or Headers)
     * @param boolean $europe ¿use european servers? Default false
     */
    public function __construct($authuser,$authpass,$europe=false){
        $this->authuser=$authuser;
        $this->authpass=$authpass;
        if ($europe) {
            $this->server='api.eu.turbo-smtp.com';
        } else {
            $this->server='api.turbo-smtp.com';
        }
        $this->endPoint='https://'.$this->server.'/api/v2/mail/send';
    }

    /**
     * Send email
     * 
     * @param string $from is the from address
     * @param string $to is provided as comma-separated recipients list
     * @param string $subject is the subject of the email (optional)
     * @param string $content is the text content of the email  (optional)
     * @param string $html_content is the html content of the email (optional)
     * @param string $cc is provided as comma-separated copy list (optional)
     * @param string $bcc is provided as comma-separated hidden copy list (optional)
     * @param string $replyTo reply to adress (optional)
     * @param array $custom_headers are additional headers, e.g. ["X-key1"=>"value1", "X-key2"=>"value2"] (optional)
     * @param string $mime_raw mime message which replaces content and html_content  (optional)
     * @param array attachments array of attachment objects
     * @return array response {"message":"OK"} or {"message": "error","errors": ["error message"] } 
     */
    public function send($from,$to,$subject=null,$content=null,$html_content=null,$cc=null,$bcc=null,$replyTo=null,$custom_headers=null,$mime_raw=null,$attachments=null) {   
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->endPoint );        		
        
        //debug
        //curl_setopt($curl, CURLOPT_VERBOSE, true);     
        
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $post=array(
            'authuser' => $this->authuser,
            'authpass' => $this->authpass,
            'from'=>$from,
            'to'=>$to,
            'subject'=>$subject,
            'cc'=>$cc,
            'bcc'=>$bcc,
            'content'=>$content,
            'html_content'=>$html_content,
            'custom_headers'=>$this->getHeaders($replyTo, $custom_headers),
            'mime_raw'=>$mime_raw,
            'attachments'=>$attachments,
         );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($curl); 
        curl_close($curl); 
        $response=json_decode($response,true);
        return $response;
    }

    /**
     * Generate a ID for custom headers
     *
     * @return string
     */

    private function generateMessageId()
    {        
        $len = 32; //32 bytes = 256 bits
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($len);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($len);
        } else {
            //Use a hash to force the length to the same as the other methods
            $bytes = hash('sha256', uniqid((string) mt_rand(), true), true);
        }
        //We don't care about messing up base64 format here, just want a random string
        $id=str_replace(array('=', '+', '/'), '', base64_encode(hash('sha256', $bytes, true)));
        return sprintf('<%s@%s>', $id,$this->server);
    }

    private function getHeaders ($replyTo, $custom_headers) {
        $headers= array (
            'Date'=>date("r"),
            'Message-Id'=>$this->generateMessageId(),
        );

        if (!empty($replyTo))
        {
        $headers['Reply-To']=trim($replyTo);
        }

        if (is_array($custom_headers))
        {
        $headers=array_merge($headers,$custom_headers);
        }
        return json_encode($headers);

    }

}