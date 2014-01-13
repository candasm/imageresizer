<?php

/**
 * Description of imageResizer
 *
 * @author candasminareci
 */
class imageResizer
{
    /**
     * resim boyutlarını al
     * resim genişliğini 45 e ayarla uzunluğu önemli değil
     * oluşan resimden x => 0 dan 45px ,y => 0 dan 45px
     * resize dan önce
     * resim genişliği > uzunluk tan büyük ise 
     * resize hesaplaması uzunluk üzerine uzunluğu 45 e sabitlenerek yeni resim oluşturulacak
     * resize dan önce
     * resim uzunluğu > genişliğinden büyük ise 
     * resize hesaplaması hedef olarak genişlik alınacak genişlik 45 e sabitlenecek uzunluk ne kadar olucaksa olabilir
     * resim xy 45,45 alınacak hesaplanan resimden 
     */

    const resim_yok = 'Resim verilen yolda bulunamadı';
    const desteklenmeyen_mime = 'Desteklenmeyen resim uzantısı';
    const resim_olusturulamadi = 'Resim oluşturulamadı';
    const resim_yok_edilemedi = 'Oluşturulan resim yok edilemedi';
    const resim_boyutlari_hesaplanmamais = 'Resim boyutları hesaplanmamış';
    const resim_yeniden_boyutlandirilamadi = 'Resim yeniden boyutlandırılamadı';
    const resim_kesme_islemi_yapilamadi = 'Resim crop lama işlemi yapılamadı';

    public $backgroundRGB = array (0, 0, 0);
    public $backgroundTrasnparent = true;
    private $source_file = '';
    public $image = '';
    private $image_info = array ();
    public $image_width = 0;
    public $image_height = 0;
    private $error = false;
    private $errors = array ();
    private $new_width = null;
    private $new_height = null;

    public function transparent($bool = true)
    {
        if (is_bool($bool))
        {
            $this->backgroundTrasnparent = $bool;
        }
        return $this;
    }

    public function backgroundcolor($r = 0, $g = 0, $b = 0)
    {
        $r = (int) $r;
        $g = (int) $g;
        $b = (int) $b;
        $this->backgroundRGB = array ($r, $g, $b);
        return $this;
    }

    /**
     * Gönderilen full resim pathini yükler
     * @param string resmin full path i
     * @return object
     */
    public function load($path)
    {
//@todo background oluşturup yükleme yaptır resim ile arkası bozuk oluyor        
        //dosya varmı diye kontrol et dosyayı yarat
        $image_info = @getimagesize($path);
        if ($image_info === FALSE)
        {
            //hata mesajı
            $this->errors[] = self::resim_yok;
            $this->error = true;
        }
        else
        {
            //hata yok ise resim yüklemesini yap
            $this->image_info = $image_info;
//          resimin genişlik yüksekliğini ayarlar  
            $this->image_width = $this->image_info[0];
            $this->image_height = $this->image_info[1];

            $this->source_file = $path;
            $image = false;

            switch ($image_info['mime'])
            {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($path);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($path);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($path);
                    break;
                default:
                    $this->error = true;
                    $this->errors[] = self::desteklenmeyen_mime;
            }

            if ($image === FALSE)
            {
                $this->error = true;
                $this->errors[] = self::resim_olusturulamadi;
            }
            //oluşturulmuş resmi genel değişkene set et
            $this->image = $image;
        }
        return $this;
    }

    /**
     * Yüklenmiş resme background yükleyerek günceller
     */
    public function loadBackground($r = 1, $g = 1, $b = 1)
    {

        $canvas = $this->imagecreate($this->image_width, $this->image_height);
        $color = imagecolorallocate($canvas, $r, $g, $b);
        imagefilledrectangle($canvas, 0, 0, $this->image_width, $this->image_height, $color);

        imagecopyresampled($canvas, $this->image, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_width, $this->image_height);

        $this->destroy();
        $this->image = $canvas;

        return $this;
    }

    /**
     * genişlik yüksekliğe göre yeni resim oluşturup değişkeni geri döndürür
     * @param int genişlik
     * @param int yükseklik
     */
    public function imageCreate($width, $height)
    {

        $canvas = imagecreatetruecolor($width, $height);

        if ($this->backgroundTrasnparent)
        {
            $black = imagecolorallocate($canvas, 1, 1, 1);
            imagecolortransparent($canvas, $black);
        }
        else
        {
            $color = imagecolorallocate($canvas, $this->backgroundRGB[0], $this->backgroundRGB[1], $this->backgroundRGB[2]);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $color);
        }
        return $canvas;
    }

    /**
     * yüklenen resmi istenen genişlik ve yüksekliğe göre yeniden boyutlandırır iki değerde NULL verildiğinde işlem yapmaz
     * değerlerden biri NULL verildiğinde diğer değer hedef alınıp yeniden boyutlandırma işlemi yapılır
     * @param int 
     * @return object
     */
    public function resize()
    {
//      istenen genişlik ve uzunluk bilgisine göre resize edilecek resmin yeni boyutlarını verir  

        if (is_null($this->new_width) || is_null($this->new_height))
        {
            $this->addError(self::resim_boyutlari_hesaplanmamais);
        }
        else
        {

            $canvas = $this->imageCreate($this->new_width, $this->new_height);

            if (imagecopyresampled($canvas, $this->image, 0, 0, 0, 0, $this->new_width, $this->new_height, $this->image_width, $this->image_height) === FALSE)
            {
                $this->addError(self::resim_yeniden_boyutlandirilamadi);
            }
            else
            {
//          oluşturulmuş ana resim olarak yükler 
                $this->destroy();
                $this->image = $canvas;
            }
        }

        return $this;
    }

    /**
     * Yüklenen resmi verilen boyuta göre yeniden boyutlandırır kare çıktı veremese bile 
     * örnek olarak 120,60 bir resmi 45 e oturtmak isterseniz çıktıyı  90,45 olarak verir böylelikle en düşük birim verilen ölçü olur
     * @param int min size
     * @return array  w,h
     */
    public function calculateSquare($size)
    {

        $swidth = $this->image_width;
        $sheight = $this->image_height;

        $yeniheight = 0;
        $yeniwidth = 0;
        $hesap = 0;
        $fark = 0;

//      istenen değer resim en düşük ölçü değerinden büyük ise        
        if ($size > $swidth || $size > $sheight)
        {
            if ($size > $sheight)
            {
                $fark = $size - $sheight;
                $hesap = round(($swidth / $sheight) * $fark);
                $yeniwidth = $swidth + $hesap;
                $yeniheight = $sheight + $fark;
            }
            if ($size > $swidth)
            {
                $fark = $size - $swidth;
                $hesap = round(( $sheight / $swidth) * $fark);
                $yeniheight = $sheight + $hesap;
                $yeniwidth = $swidth + $fark;
            }
        }
        else
        {
//      istenen değer her iki değerdende küçük ise
//      100 > 50       50-45 = 5            100/50 * 5 => 10 
            if ($swidth > $sheight)
            {
//           genişlik yükseklikten büyük ise
//              küçük olan değerden istenen değeri çıkart
                $fark = $sheight - $size;
                $hesap = round(($swidth / $sheight) * $fark);
                $yeniwidth = $swidth - $hesap;
                $yeniheight = $sheight - $fark;
            }
            else if ($sheight > $swidth)
            {
//      50>100           
                $fark = $swidth - $size;
                $hesap = round(($sheight / $swidth) * $fark);
                $yeniheight = $sheight - $hesap;
                $yeniwidth = $swidth - $fark;
            }
            else
            {
//          iki değerde eşitse
                $fark = $swidth - $size;
                $yeniheight = $sheight - $fark;
                $yeniwidth = $swidth - $fark;
            }
        }
        $this->new_height = $yeniheight;
        $this->new_width = $yeniwidth;

        return $this;
    }

    /**
     * istenen genişlik ve uzunluk bilgisine göre resize edilecek resmin boyut hesaplamasını yapar
     * @todo yüzdeli olarak boyutlandırma eklenebilir
     * @param int $istenenGenislik
     * @param int $istenenYukseklik
     * @return object
     */
    public function calculateResize($istenenGenislik, $istenenYukseklik)
    {
        $orjinalGenislik = $this->image_width; //1189
        $orjinalYukseklik = $this->image_height; //651

//      her iki ölçüde null ise resize etme          
        if ($istenenGenislik == NULL && $istenenYukseklik == NULL)
        {
            $istenenYukseklik = $orjinalYukseklik;
            $istenenGenislik = $orjinalGenislik;
        }
        /**
         * En az ölçülerden biri verildiğinde otomatik olarak diğer ölçüyü hesaplar
         * Her iki ölçü verildiğinde ise istenen ölçülere oturtmaya çalışır!
         */
        if ($istenenGenislik != NULL || $istenenYukseklik != NULL)
        {
            /**
             * istenen genislik belirtilmediyse istenen yuksekligin boyutlandırma orani ile orjinal genişlik çarpılarak yeni genişlik oranı bulunur!
             */
            if($istenenGenislik==NULL){
                $istenenBoyutlandirmaOrani = $istenenYukseklik/$orjinalYukseklik;
                $istenenGenislik = round( $orjinalGenislik * $istenenBoyutlandirmaOrani );
            }
            if($istenenYukseklik==NULL){
                $istenenBoyutlandirmaOrani = $istenenGenislik/$orjinalGenislik ;
                $istenenYukseklik = round( $orjinalYukseklik * $istenenBoyutlandirmaOrani );
            }
            
            /**
             * resim yatay ise
             */
            if ($orjinalGenislik > $orjinalYukseklik)
            {
                $istenenGenisliginOrjinalGenisligeOrani = ($istenenGenislik / $orjinalGenislik);   // 
                $hesap = round($orjinalYukseklik * $istenenGenisliginOrjinalGenisligeOrani); 
                // make sure the new dimensions fit into defined space
                if ($hesap <= $istenenYukseklik)
                {
                    $istenenYukseklik = $hesap;
                }
                else
                {
                    $istenenYuksekliginOrjinalYuksekligeOrani = ($istenenYukseklik / $orjinalYukseklik);
                    $istenenGenislik = round($orjinalGenislik * $istenenYuksekliginOrjinalYuksekligeOrani);
                }
            }
            /**
             * resim dikey veya kare ise
             */
            else
            {
                $istenenYuksekliginOrjinalYuksekligeOrani = ($istenenYukseklik / $orjinalYukseklik); //200/600  0.3
                $hesap = round($orjinalGenislik * $istenenYuksekliginOrjinalYuksekligeOrani) ; // 480 * 0.3 
                // make sure the new dimensions fit into defined space
                if ($hesap <= $istenenGenislik)
                {
                    $istenenGenislik = $hesap;
                }
                else
                {
                    $istenenGenisliginOrjinalGenisligeOrani = ($istenenGenislik / $orjinalGenislik);
                    $istenenYukseklik = round($orjinalYukseklik * $istenenGenisliginOrjinalGenisligeOrani);
                }
            }
        }

        $this->new_height = $istenenYukseklik;
        $this->new_width = $istenenGenislik;
        return $this;
    }

    /**
     * Oluşturulmuş resim'den verilen boyutlarda kesilmiş halini yeniden oluşturur ve image yerine set eder
     * @param int $width
     * @param int $height
     * @param int $top
     * @param int $left
     * @return object
     */
    public function crop($width, $height, $top = 0, $left = 0)
    {

//  width height değerlerine göre yeni bir canvas oluştur resmin bu değerlerinden gerekli kısmı al üzerine yapıştır      
        
        if(!$width){
            $width=$this->new_width;
        }
        if(!$height){
            $height=$this->new_height;
        }
        
        
        $canvas = $this->imageCreate($width, $height);

        if (@imagecopyresampled($canvas, $this->image, 0, 0, 0, 0, $width, $height, $width, $height) === FALSE)
        {
            $this->addError(self::resim_kesme_islemi_yapilamadi);
            $this->destroy($canvas);
        }
        else
        {
            $this->destroy();
            $this->image = $canvas;
            $this->image_width = $width;
            $this->image_height = $height;
        }
        return $this;
    }

    /**
     * paramatere verilmez ise yüklenen resmi siler verilmiş ise gönderilen i siler
     * @param string image result
     * @retun object
     */
    public function destroy($image = null)
    {

        $destroy_data = is_null($image) ? $this->image : $image;
        if (@imagedestroy($destroy_data) === FALSE)
        {
            $this->addError(self::resim_yok_edilemedi);
        }

        return $this->error ? false : true;
    }

    /**
     * Oluşturulmuş resmi verilen path'e kaydeder
     * @param string kaydedilicek dosya yolu ve dosya adı
     * @return object
     */
    function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = 0777)
    {

        if ($image_type == IMAGETYPE_JPEG)
        {
            imagejpeg($this->image, $filename, $compression);
        }
        elseif ($image_type == IMAGETYPE_GIF)
        {

            imagegif($this->image, $filename);
        }
        elseif ($image_type == IMAGETYPE_PNG)
        {

            imagepng($this->image, $filename);
        }
        if ($permissions != null)
        {

            chmod($filename, $permissions);
        }
    }

    /**
     * Oluşturulmuş resmi browser'a çıktı olarak verir
     */
    public function render()
    {
        $func = str_replace('/', '', $this->image_info['mime']);
        header('Content-Type: ' . $this->image_info['mime']);
        $func($this->image);
        $this->destroy();
    }

    /**
     * Sınıf hata mesajı ekler
     * @param string hata mesajı
     */
    private function addError($error)
    {
        $this->error = true;
        $this->errors[] = $error;
    }
    
    public function  getExtension($imageName=null){
        $imageSource = $imageName==null? $this->source_file : $imageName;
        $dotArray = explode('.',$imageSource);
        if(count($dotArray)==0) return '';
        return $dotArray[count($dotArray)-1];
    }
}