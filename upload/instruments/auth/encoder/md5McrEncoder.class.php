<?php
loadTool('Md5Encoder.class.php', 'auth/encoder/');

class Md5McrEncoder extends Md5Encoder implements EncoderInterface
{
    private $iterations = 256;
    private $bin = true;
    private $salt = true;
}
