# AIGF Haber Ajansı Wordpress Botu

## Kurulum
aigf.php dosyasını wordpress'in kurulu olduğu ana dizine atınız.
www.websiteniz.com/aigf.php sayfası açıldığında WORDPRESS OTOMATİK İÇERİK ALMAK İÇİN HAZIR...  yazısını görüyorsanız herşey yolundadır.

## Ayarlar
Haberlerin ilgili kategorilerde gösterilebilmesi için $aigf_cats değişkenini site kategorinize göre değiştirmeniz gerekmektedir.
### Kategori Eşleştirme
Örneğin AIGF "GÜNDEM" Haberlerini web sitenizin "GÜNCEL" kategorinde yayınlamak istiyorsanız, "GÜNDEM" değişkeninin değerini web sitenizin "GÜNCEL" kategorisinin ID si ile değiştirmelisiniz.

$aigf_cats = array(
	"GÜNDEM"	=>"2", 
	"EĞİTİM"	=>"3", 
	"MAGAZİN"	=>"4",
	"SPOR"		=>"5",
	"EKONOMİ"	=>"6",
	"SAĞLIK"	=>"7",
	"SİYASET"	=>"8",
	"TEKNOLOJİ"	=>"9",
	"YAŞAM"		=>"10",
	"ASAYİŞ"	=>"11",
	"DÜNYA"		=>"12"
);

### Özel Alanlar
Tema ayarlarına göre eklenen haberlere özel alan eklemek isterseniz $aigf_customs değişkenini kullanabilirsiniz.
Örneğin : $aigf_customs['aigf_source']		= $source; değişkeni aigf_source özel alanı ekler
### Post Ayarları
$aigf_post['post_type'] 	= "post";
$aigf_post['post_status'] 	= "publish"; //Eklenen haberleri hemen yayınlamak için "publish" taslaklara kaydetmek için "draft" olarak değiştirebilirsiniz.
$aigf_post['post_author'] 	= "1" ;


## Otomatik haber çekme
Sunucu veya hosting panelinizden zamanlanmış görev ekleyerek dosyanın belirli aralıklarla çalışmasını sağlayınız.
Dikkat : Dosya çalıştırma aralığı en az 1 dakika olmalıdır.
