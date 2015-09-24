<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Skip32 php implementation
 * 32-bit block cipher based on Skipjack
 *
 * Adaptation of a direct Perl translation of the SKIP32 C implementation
 * http://search.cpan.org/~esh/Crypt-Skip32/lib/Crypt/Skip32.pm
 * http://www.qualcomm.com.au/PublicationsDocs/skip32.c
 *
 * @example
 *
 *   $key = pack('H20', '0123456789abcdef0123'); // 10 bytes key
 *   $cipher = new Skip32Cipher($key);
 *
 *   $int = 4294967295; // 4 bytes integer
 *
 *   $bin = pack('N', $int);
 *   $encrypted = $cipher->encrypt($bin);
 *   list(, $encryptedInt) = unpack('N', $encrypted);
 *
 *   printf("%d encrypted to %d\n", $int, $encryptedInt);
 *
 *   $bin = pack('N', $encryptedInt);
 *   $decrypted = $cipher->decrypt($bin);
 *   list(, $decryptedInt) = unpack('N', $decrypted);
 *
 *   printf("%d decrypted to %d\n", $encryptedInt, $decryptedInt);
 *
 * This will display (on 64-bit architecture) :
 *
 *   4294967295 encrypted to 572455217
 *   572455217 decrypted to 4294967295
 *
 * @author Nicolas Lenepveu <n.lenepveu@gmail.com>
 */
class Skip32Cipher
{
    /**
     * @const Number of bytes in the key
     */
    const KEY_SIZE = 10;

    /**
     * @const Number of bytes in the data.
     */
    const BLOCK_SIZE = 4;

    private $_ftable = array(
        0xa3,0xd7,0x09,0x83,0xf8,0x48,0xf6,0xf4,0xb3,0x21,0x15,0x78,0x99,0xb1,0xaf,0xf9,
        0xe7,0x2d,0x4d,0x8a,0xce,0x4c,0xca,0x2e,0x52,0x95,0xd9,0x1e,0x4e,0x38,0x44,0x28,
        0x0a,0xdf,0x02,0xa0,0x17,0xf1,0x60,0x68,0x12,0xb7,0x7a,0xc3,0xe9,0xfa,0x3d,0x53,
        0x96,0x84,0x6b,0xba,0xf2,0x63,0x9a,0x19,0x7c,0xae,0xe5,0xf5,0xf7,0x16,0x6a,0xa2,
        0x39,0xb6,0x7b,0x0f,0xc1,0x93,0x81,0x1b,0xee,0xb4,0x1a,0xea,0xd0,0x91,0x2f,0xb8,
        0x55,0xb9,0xda,0x85,0x3f,0x41,0xbf,0xe0,0x5a,0x58,0x80,0x5f,0x66,0x0b,0xd8,0x90,
        0x35,0xd5,0xc0,0xa7,0x33,0x06,0x65,0x69,0x45,0x00,0x94,0x56,0x6d,0x98,0x9b,0x76,
        0x97,0xfc,0xb2,0xc2,0xb0,0xfe,0xdb,0x20,0xe1,0xeb,0xd6,0xe4,0xdd,0x47,0x4a,0x1d,
        0x42,0xed,0x9e,0x6e,0x49,0x3c,0xcd,0x43,0x27,0xd2,0x07,0xd4,0xde,0xc7,0x67,0x18,
        0x89,0xcb,0x30,0x1f,0x8d,0xc6,0x8f,0xaa,0xc8,0x74,0xdc,0xc9,0x5d,0x5c,0x31,0xa4,
        0x70,0x88,0x61,0x2c,0x9f,0x0d,0x2b,0x87,0x50,0x82,0x54,0x64,0x26,0x7d,0x03,0x40,
        0x34,0x4b,0x1c,0x73,0xd1,0xc4,0xfd,0x3b,0xcc,0xfb,0x7f,0xab,0xe6,0x3e,0x5b,0xa5,
        0xad,0x04,0x23,0x9c,0x14,0x51,0x22,0xf0,0x29,0x79,0x71,0x7e,0xff,0x8c,0x0e,0xe2,
        0x0c,0xef,0xbc,0x72,0x75,0x6f,0x37,0xa1,0xec,0xd3,0x8e,0x62,0x8b,0x86,0x10,0xe8,
        0x08,0x77,0x11,0xbe,0x92,0x4f,0x24,0xc5,0x32,0x36,0x9d,0xcf,0xf3,0xa6,0xbb,0xac,
        0x5e,0x6c,0xa9,0x13,0x57,0x25,0xb5,0xe3,0xbd,0xa8,0x3a,0x01,0x05,0x59,0x2a,0x46
    );

    private $_key;

    /**
     * Cipher constructor
     *
     * @param string $key 10 bytes key
     */
    public function __construct($key)
    {
        $key = unpack("C*", $key);
        if (count($key) != self::KEY_SIZE) {
            throw new Exception(sprintf("Key must be %d bytes long", self::KEY_SIZE));
        }

        $this->_key = array_values($key);
    }

    /**
     * Encrypt 32-bit binary data
     *
     * @param string $data 4 bytes block
     *
     * @return string 4 bytes block
     */
    public function encrypt($data)
    {
        return $this->_skip32($data, true);
    }

    /**
     * Decrypt 32-bit binary data
     *
     * @param string $data 4 bytes block
     *
     * @return string 4 bytes block
     */
    public function decrypt($data)
    {
        return $this->_skip32($data, false);
    }

    private function _g($k, $w)
    {
        $g1 = ($w>>8) & 0xff;
        $g2 = $w & 0xff;

        $g3 = $this->_ftable[$g2 ^ $this->_key[(4 * $k) % 10]] ^ $g1;
        $g4 = $this->_ftable[$g3 ^ $this->_key[(4 * $k + 1) % 10]] ^ $g2;
        $g5 = $this->_ftable[$g4 ^ $this->_key[(4 * $k + 2) % 10]] ^ $g3;
        $g6 = $this->_ftable[$g5 ^ $this->_key[(4 * $k + 3) % 10]] ^ $g4;

        return (($g5 << 8) + $g6);
    }

    private function _skip32($buf, $encrypt)
    {
        $buf = unpack("C*", $buf);
        if (count($buf) != self::BLOCK_SIZE) {
            throw new Exception(sprintf("Data must be %d bytes long", self::BLOCK_SIZE));
        }

        // sort out direction
        if ($encrypt) {
            $kstep = 1;
            $k = 0;
        } else {
            $kstep = -1;
            $k = 23;
        }

        // pack into words
        $wl = ($buf[1] << 8) + $buf[2];
        $wr = ($buf[3] << 8) + $buf[4];

        // 24 feistel rounds, doubled up
        for ($i = 0; $i < 24/2; ++$i) {
            $wr ^= self::_g($k, $wl) ^ $k;
            $k += $kstep;
            $wl ^= self::_g($k, $wr) ^ $k;
            $k += $kstep;
        }

        // implicitly swap halves while unpacking
        $buf[1] = $wr >> 8;
        $buf[2] = $wr & 0xFF;
        $buf[3] = $wl >> 8;
        $buf[4] = $wl & 0xFF;

        return pack("C*", $buf[1], $buf[2], $buf[3], $buf[4]);
    }

}

/**
 * A Simple Skip32 php implementation
 * 32-bit block cipher based on Skipjack
 *
 * @example
 *
 *   $key = '0123456789abcdef0123'; // 10 bytes key
 *   $int = 4294967295; // 4 bytes integer
 *
 *   $encrypted = Skip32::encrypt($key, $int);
 *   $decrypted = Skip32::decrypt($key, $encrypted);
 *
 *   printf("%d encrypted to %d\n", $int, $encrypted);
 *   printf("%d decrypted to %d\n", $encrypted, $decrypted);
 *
 * This will display (on 64-bit architecture) :
 *
 *   4294967295 encrypted to 572455217
 *   572455217 decrypted to 4294967295
 *
 * @author Nicolas Lenepveu <n.lenepveu@gmail.com>
 */
class Skip32
{
    const DEFAULT_KEY_BASE = 16;

    const DEFAULT_BLOCK_BASE = 10;

    private $_cipher;

    public function __construct() {

    }

    /**
     * Simple way to encrypt a 4 bytes long ASCII string
     *
     * @param string $key  ASCII representation of a 10 bytes long key
     * @param int    $data 4 bytes block integer
     *
     * @return string
     */
    public function encrypt($key, $data)
    {
        // $simple = new self($key);
        $this->_gen_cipher($key);
        return $this->enc($data);
    }

    /**
     * Simple way to decrypt a 4 bytes long ASCII string
     *
     * @param string $key  ASCII representation of a 10 bytes long key
     * @param int    $data 4 bytes block integer
     *
     * @return string
     */
    public function decrypt($key, $data)
    {
        // $simple = new self($key);
        $this->_gen_cipher($key);
        return $this->dec($data);
    }

    /**
     * 生成唯一的序列号
     * @author yugang@dachuwang.com
     * @description 序列号组成规则：两位字母+10位加密数字
     * 两位字母前缀（代表单据类型
     * 10位skp32算法生成的加密后的数字,10位加密数字中间插入两位随机数字
     * 加密KEY：固定字符串（开发机和正式机不一致，保密）
     * 加密前数字：采用数据库自增字段,确保不会重复
     */
     function get_serial_no($counter) {
        $key = C('encryption.skip32.key');
        $enc_data = $this->encrypt($key, $counter);
        $serial_no = str_pad($enc_data, 10, '0', STR_PAD_LEFT);
        $rand = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
        $serial_no = substr($serial_no, 0, 5) . $rand . substr($serial_no, 5, 5);
        return $serial_no;
    }


    /**
     * Cipher constructor
     *
     * @param string $key ASCII representation of a 10 bytes long key
     */
    public function _gen_cipher($key)
    {
        $key = $this->_binarize($key, self::DEFAULT_KEY_BASE, Skip32Cipher::KEY_SIZE);
        $this->_cipher = new Skip32Cipher($key);
    }

    /**
     * Encrypt a 4 bytes long ASCII string
     *
     * @param string $data ASCII representation of a 4 bytes block
     *
     * @return string
     */
    public function enc($data)
    {
        return $this->_simplify('encrypt', $data);
    }

    /**
     * Decrypt a 4 bytes long ASCII string
     *
     * @param string $data ASCII representation of a 4 bytes block
     *
     * @return string
     */
    public function dec($data)
    {
        return $this->_simplify('decrypt', $data);
    }

    /**
     * Simplify the use of Skip32 Cipher
     *
     * @param string $method Method of the Cipher (encrypt|decrypt)
     * @param string $data   ASCII representation of a 4 bytes block
     *
     * @return string
     */
    private function _simplify($method, $data)
    {
        $data = $this->_binarize($data, self::DEFAULT_BLOCK_BASE, Skip32Cipher::BLOCK_SIZE);

        $data = $this->_cipher->$method($data);

        $data = current(unpack("H8", $data));
        $data = base_convert($data, 16, self::DEFAULT_BLOCK_BASE);

        return $data;
    }

    /**
     * Binarize an ASCII representation of bytes
     *
     * @param string $data ASCII representation of bytes
     * @param int    $n    number of bytes expected
     *
     * @return string
     */
    private function _binarize($data, $base, $n)
    {
        if ($base != 16) {
            $data = base_convert($data, $base, 16);
        }

        $len = $n * 2;
        $hex = sprintf("%0{$len}s", $data);
        return pack("H{$len}", $hex);
    }

}

