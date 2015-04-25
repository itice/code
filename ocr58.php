<?php
/**
 * 58同城获取手机号码插件
 * @author zxb<2014.09.13>
 *
 */
class ocr58 {
    public function __construct($img) {
        $this->img = $img;
        $this->imginfo = $this->getImgInfo ();
        $createimgfunc = 'imagecreatefrom' . $this->imginfo ['type'];
        $this->im = $createimgfunc ( $this->img );
        $this->data = array (
                0 => array (
                        0 => '11100000000001111111110000111111',
                        1 => '11000000000000111110000000000111' 
                ),
                1 => array (
                        0 => '01111000000000111111111111111111',
                        1 => '01111000000000111111111111111111' 
                ),
                2 => array (
                        0 => '11100000111110111111001111110011',
                        1 => '11000000011110111110000011111011' 
                ),
                3 => array (
                        0 => '11100011100001111111011111101111',
                        1 => '11000001100000111110001111000111' 
                ),
                4 => array (
                        0 => '11111000001100001111111111111111',
                        1 => '01111100001100001111000000110000' 
                ),
                5 => array (
                        0 => '11000110000000111100011100000111',
                        1 => '11000110000000111100011000000011' 
                ),
                6 => array (
                        0 => '11000011100001111110001111001111',
                        1 => '11000011000000111110001110000111' 
                ),
                7 => array (
                        0 => '11000111111111111101111111111100',
                        1 => '11000011111111111100111111111110' 
                ),
                8 => array (
                        0 => '11100011110000111111011111111111',
                        1 => '11000111110000111110011111000111' 
                ),
                9 => array (
                        0 => '11100001110001111111001111111111',
                        1 => '11000000110001111110000111000111' 
                ) 
        );
    }
    
    /**
     * 获取号码
     *
     * @return array
     */
    public function getPhone() {
        $key_start = 17 * 11 / 2;
        $key_len = 34;
        
        $all_two_var = $this->getTwoVar ( 1, 8, $this->imginfo ['width'] - 13, $this->imginfo ['height'] - 5 );
        $word_width = ($this->imginfo ['width'] - 12 - 2) / 11;
        
        $rel = array ();
        foreach ( $this->data as $num => $keys ) {
            foreach ( $keys as $key ) {
                $pos_arr = $this->strpos ( $all_two_var, $key );
                if (! empty ( $pos_arr )) {
                    foreach ( $pos_arr as $pos ) {
                        $n = round ( $pos / ($word_width * 17) );
                        if ($pos < 100)
                            $n = 0;
                        $rel [$n] = $num;
                    }
                }
            }
        }
        
        for($i = 0; $i < 11; $i ++) {
            $result [] = isset ( $rel [$i] ) ? $rel [$i] : 'x';
        }
        return $result;
    }
    
    /**
     * 获取图像从a($x1,$y1)点到b($x2,$y2)点的二值码
     *
     * @param int $x1           
     * @param int $y1           
     * @param int $x2           
     * @param int $y2           
     * @return string
     */
    private function getTwoVar($x1, $y1, $x2, $y2) {
        $string = '';
        for($x = $x1; $x <= $x2; $x ++) {
            for($y = $y1; $y <= $y2; $y ++) {
                $color = imagecolorat ( $this->im, $x, $y );
                $string .= $color == 31 ? '0' : '1';
            }
        }
        return $string;
    }
    
    /**
     * 获取图像信息（宽、高、类型）
     *
     * @return array
     */
    private function getImgInfo() {
        $type_arr = array (
                '',
                'gif',
                'jpeg',
                'png' 
        );
        $imginfo = getimagesize ( $this->img );
        $info ['width'] = $imginfo [0];
        $info ['height'] = $imginfo [1];
        $info ['type'] = $type_arr [$imginfo [2]];
        return $info;
    }
    
    /**
     * 获取字符key在str中出现的所有位置
     *
     * @param string $str           
     * @param string $key           
     * @return array
     */
    private function strpos($str, $key) {
        $pos_arr = array ();
        $pos = TRUE;
        while ( $pos ) {
            $pos = strpos ( $str, $key );
            if ($pos !== FALSE) {
                $pos_arr [] = $pos;
                $str = substr_replace ( $str, str_repeat ( 'x', strlen ( $key ) ), $pos, strlen ( $key ) );
            }
        }
        return $pos_arr;
    }
}