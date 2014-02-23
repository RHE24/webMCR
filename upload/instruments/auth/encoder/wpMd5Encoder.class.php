<?php

class WpEncoderDriver extends EncoderAbstract implements EncoderInterface
{
    public function checkPass($hash, $password, $user)
    {
        $cryptPass = false;
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $count_log2 = strpos($itoa64, $hash[3]);
        $count = 1 << $count_log2;
        $salt = substr($hash, 4, 8);
        $input = md5($salt . $password, true);
        do {
            $input = md5($input . $password, true);
        } while (--$count);

        $output = substr($hash, 0, 12);

        $count = 16;
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $cryptPass .= $itoa64[$value & 0x3f];
            if ($i < $count)
                $value |= ord($input[$i]) << 8;
            $cryptPass .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count)
                break;
            if ($i < $count)
                $value |= ord($input[$i]) << 16;
            $cryptPass .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count)
                break;
            $cryptPass .= $itoa64[($value >> 18) & 0x3f];
        }
        while ($i < $count);

        $cryptPass = $output . $cryptPass;

        if ($hash == $cryptPass)
            return true;
        else
            return false;
    }
}
