<?php
class YmtMcrypt {
    const DEFAULT_ENCYPT_KEY = "1f8piWNeL/DZ3liPMQrk35T8De8qPW2x4kZ6IeMeN22pAayxp68UYtpDtoCkPP4A0gBcMLOjqtAePSp2w2YuOw==";
    const KEY_SIZE = 16;


    private function get_app_key(){
        //TODO unsigned byte check
        $data = base64_decode(YmtMcrypt::DEFAULT_ENCYPT_KEY);
        $len = strlen($data);

        $bytes1 = array();
        for($j = 0; $j < $len; $j++){
            if(ord($data[$j]) > 128){
                $bytes1[] = ord($data[$j]) - 256;
            }else{
                $bytes1[] = ord($data[$j]);
            }
        }

        $seed = 0x1a464569;
        $bytes2 = array();
        for($j = 0; $j < $len; $j++)
        {
            $l1 = (12345 + 0x41c64e6d * $seed) % 0x100000000;
            $byte = (int)(($bytes1[$j] + $l1 % 256) % 256);
            if($byte > 128){
                $byte -= 256;
            }
            $bytes2[] = $byte;
            $seed = (12345 + 0x41c64e6d * $l1) % 0x100000000;
        }

        $result = "";
        for($j = 0; $j < $len; $j++){
            $result .= chr($bytes2[$j]);
        }

        return $result;
    }

    /**
     * pkcs7补码
     *
     * @param string $string  明文
     * @param int $blocksize Blocksize , 以 byte 为单位 !same with IV
     *
     * @return String
     */
    private function addPkcs7Padding($string, $blocksize = 32) {
        $len = strlen($string); //取得字符串长度
        $pad = $blocksize - ($len % $blocksize); //取得补码的长度
        $string .= str_repeat(chr($pad), $pad); //用ASCII码为补码长度的字符， 补足最后一段
        return $string;
    }

    /**
     * 除去pkcs7 padding
     *
     * @param String 解密后的结果
     *
     * @return String
     */
    private function stripPkcs7Padding($string){
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        if(preg_match("/$slastc{".$slast."}/", $string)){
            $string = substr($string, 0, strlen($string)-$slast);
            return $string;
        } else {
            return false;
        }
    }

    public function encrypt($data){
        $privateKey = substr($this->get_app_key(),0,YmtMcrypt::KEY_SIZE);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $privateKey, $this->addPkcs7Padding($data,16), MCRYPT_MODE_CBC, $iv);
        $ciphertext = $iv . $ciphertext;

        return $this->urlbase64_encode($ciphertext);
    }

    public function deEncrypt($ciphertext_base64){
        $privateKey = substr($this->get_app_key(),0,YmtMcrypt::KEY_SIZE);

        $ciphertext_dec = $this->urlbase64_decode($ciphertext_base64);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);

        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $data_raw = $this->stripPkcs7Padding(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $privateKey,$ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec));
        return $data_raw;
    }

    private function hMac($data,$key){
        return $this->urlbase64_encode(mhash(MHASH_SHA1,$data,$key));
    }

    private function urlbase64_encode($data){
        $base64_encode_data = base64_encode($data);

        //transform by URL Base64
        //http://blog.csdn.net/lonelyroamer/article/details/7638435
        $urlbase64_data = str_replace("+","-",$base64_encode_data);
        $urlbase64_data = str_replace("/","_",$urlbase64_data);
        $urlbase64_data = str_replace("=",".",$urlbase64_data);
        return $urlbase64_data;
    }

    private function urlbase64_decode($urlbase64_data){
        //transform by URL Base64
        //http://blog.csdn.net/lonelyroamer/article/details/7638435
        $base64_encode_data = str_replace("-","+",$urlbase64_data);
        $base64_encode_data = str_replace("_","/",$base64_encode_data);
        $base64_encode_data = str_replace(".","=",$base64_encode_data);

        return base64_decode($base64_encode_data);
    }
}
