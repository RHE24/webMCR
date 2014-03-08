<?php
loadTool('Md5Encoder.class.php', 'auth/encoder/');

class DleEncoder extends Md5Encoder implements EncoderInterface
{
    private $iterations = 2;
}
