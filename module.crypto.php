<?php

/* 모듈 객체 초기화 */
if (!isset($module))
    $module = new class {};

/* 모듈 생성 */
$module->{"crypto"} = new class {

    const METHOD = 'aes-256-ctr';

    /**
     * 암호화
     */
    public function encrypt($message, $key, $encode = true) {
        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        if ($encode) {
            return base64_encode($nonce.$ciphertext);
        }
        return $nonce.$ciphertext;
    }


    /**
     * 복호화
     */
    public function decrypt($message, $key, $encode = true) {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );
        return $plaintext;
    }

    /**
     * 해시
     */
     public function hash($message) {
         return hash("sha256", $message);
     }
}
?>