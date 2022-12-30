<?php
/*
	AIGF.org.tr -> WORDPRESS RSS IMPORTER v1.0.0
	Bu dosya AIGF ajansından otomatik olarak haber ekler.
	
	Kurulum : 
	Bu dosyayı www.websiteniz.com/aigf.php şeklinde erişilecek şekilde FTP ile ana dizine ekleniyiniz.
	Bu dosyası hosting yönetim panelininizden 5dk bir çalışacak şekilde zamanlanmış görev ekleyerek kullanabilirsiniz.
	Dikkat : AIGF sunucularından 1dk aralıklarla istekte bulunabilirsiniz.
	
*/

require_once "wp-config.php";
header ("Content-type: text/html; charset=utf-8");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE );

// Kategori Eşleştirme Ayarları
// echo get_cat_ID("GÜNDEM") ;

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


$aigf_customs = array();
$aigf_post = array();
		
$aigf_post['post_type'] 	= "post";
$aigf_post['post_status'] 	= "publish";
$aigf_post['post_author'] 	= "1" ;

// Önce eklenenen haberler güncellenecekse true güncellenmeyecekse false olmalı
$aigf_guncelleme = false ;

aigf_init();

//******************************************************* Ayarlar Biter

function aigf_init(){
	
	$aigf_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if ( ! $aigf_conn ) {echo '<p style="color: #A00;"><b>Kritik Hata:</b>wp-config.php dosyası include edilmemiş';} else {echo "WORDPRESS OTOMATİK İÇERİK ALMAK İÇİN HAZIR... v1.0.0<hr>";mysqli_close($aigf_conn);};
	
	//mysqli_query("SET NAMES 'UTF8'");
	//mysqli_query("SET CHARACTER SET UTF8");
	//mysqli_query("SET COLLATION_CONNECTION = 'utf8_general_ci'");
	
	if (ini_get('allow_url_fopen') != 1) {
		echo '<p style="color: #A00;"><b>Kritik Hata:</b> Lütfen hosting/sunucu hizmeti aldığınız firma ile iletişime geçerek <b>allow_url_fopen</b> özelliğinin aktif edilmesini sağlayınız...</p>';
		die();
	}

	if (!is_curl_installed()) {
		echo '<p style="color: #A00;"><b>Kritik Hata:</b> Lütfen hosting/sunucu hizmeti aldığınız firma ile iletişime geçerek <b>cUrl</b> eklentisinin aktif edilmesini sağlayınız...</p>';
		die();
	}
	
	if (isset($_GET["update"])){
		 aigf_getUpdate() ;
		 die();
	}
	
	aigf_addIcerik("https://rss.aigf.org.tr/") ;
}

function aigf_addIcerik($link) {
	
	global $aigf_cats, $aigf_post, $aigf_customs ;
	
	$html	= aigf_downloadFile($link);
	$dom_data	= $html['data'];
	$dom_status	= $html['code'];

	if ( $dom_status != 200) {
		echo $dom_status . " -> " . $dom_data;
		die();
	}
	
	$dom = new DOMDocument();
	$dom->formatOutput = TRUE;
	$dom->loadXML($dom_data);
	
	
	$items = $dom->getElementsByTagName("item");
	
	foreach( $items as $item ){
	
		$nodeImages			= $item->getElementsByTagName("images")->item(0);
		$nodeVideos 		= $item->getElementsByTagName("videos")->item(0);
		
		//Node Root 
		$title			= aigf_formatSQL($item->getElementsByTagName("title")->item(0)->nodeValue) ;
		$description	= aigf_formatSQL($item->getElementsByTagName("description")->item(0)->nodeValue);
		$content 		= $item->getElementsByTagName("encoded")->item(0)->nodeValue;
		$tags			= $item->getElementsByTagName("tags")->item(0)->nodeValue;
		$pubDate 		= $item->getElementsByTagName("pubDate")->item(0)->nodeValue;	
		$upDate 		= $item->getElementsByTagName("pubDate")->item(0)->getAttribute("update");	
		$guid 			= $item->getElementsByTagName("guid")->item(0)->nodeValue;
		$category 		= $item->getElementsByTagName("category")->item(0)->nodeValue;
		$source 		= $item->getElementsByTagName("source")->item(0)->nodeValue;
		$section 		= $item->getElementsByTagName("section")->item(0)->nodeValue;
		$editor 		= $item->getElementsByTagName("editor")->item(0)->nodeValue;
		$city 			= $item->getElementsByTagName("city")->item(0)->nodeValue;
		$lang 			= $item->getElementsByTagName("lang")->item(0)->nodeValue;
		$policy 		= $item->getElementsByTagName("policy")->item(0)->nodeValue;
		$important 		= $item->getElementsByTagName("important")->item(0)->nodeValue;
		$status 		= intval($item->getElementsByTagName("status")->item(0)->nodeValue);
		$post_image_src = $item->getElementsByTagName("image")->item(0)->nodeValue;
		
		$aigf_customs['aigf_important'] 	= $important;
		$aigf_customs['aigf_policy'] 		= $policy;
		$aigf_customs['aigf_lang']			= $lang;
		$aigf_customs['aigf_city']			= $city;
		$aigf_customs['aigf_editor']		= $editor;
		$aigf_customs['aigf_source']		= $source;
		$aigf_customs['aigf_section']		= $section;
		$aigf_customs['aigf_update']		= $upDate;
		
		$aigf_post['post_date_gmt'] = $pubDate;		
		$aigf_post['post_title'] 	= $title;		
		$aigf_post['post_content'] = $content ;
		$aigf_post['post_excerpt'] = $description ;
		$aigf_post['post_category']= array($aigf_cats[$category]);
		$aigf_post['tags_input'] 	= $tags;
		
		
		//haber varmı ?
		$aigf_postvarmi = aigf_getpostid($title);
		

		// İÇERİĞİ SİL 
		if ( $status == 9  ) { 
			wp_delete_post( $aigf_postvarmi, true ); 
		} 
		
		// İÇERİĞİ GÜNCELLE 
		if ( $aigf_postvarmi!=0  ) {
			if ( $aigf_guncelleme == false ) {
				die();
			}
  			$aigf_post['ID'] = $aigf_postvarmi ; 
			$aigf_post_id = wp_update_post( $aigf_post ); 
			
			if ( $aigf_customs )  {
				foreach( $aigf_customs as  $key => $value ){
					delete_post_meta($aigf_post_id,  $key );
					add_post_meta($aigf_post_id, $key,$value ); 
				} 
			}
			
			if ( $post_image_src!="" ){
				e_addFeatureImage($post_image_src, $aigf_post_id, $title,1)	;			
				//add_post_meta($aigf_post_id, $custom->getAttribute("name"),$custom->nodeValue ); 
			}
				
			echo $title . " -> Post güncellendi...<br>";
			
		//İÇERİĞİ EKLE
		} else {		
						
			$aigf_post_id = wp_insert_post( $aigf_post ); 
		
			if ($aigf_post_id > 0 ) {
	
				wp_set_post_tags( $aigf_post_id, $aigf_post["tags_input"], true );
				
				if ( $post_image_src!="" ){
					aigf_addFeatureImage($post_image_src, $aigf_post_id, $title,1)	;			
					//add_post_meta($aigf_post_id, $custom->getAttribute("name"),$custom->nodeValue ); 
				}
				
				if ( $aigf_customs )  {
					foreach( $aigf_customs as  $key => $value ){
						add_post_meta($aigf_post_id, $key ,$value ); 
					} 
				}
				
				$post_url = get_permalink( $aigf_post_id  );
				echo $post_url. " -> Post eklendi...<br>";
				
			} else {
				echo $title . " -> Post eklenmedi!...<br>";
			}
			
		}  
	} 
}

 
	
function aigf_addFeatureImage($image_url, $post_id, $title, $setThumb){
        $datax      = aigf_downloadFile($image_url);
        $image_data = $datax['data'];
        $code       = $datax['code'];
        
        if($code!=200) return false;
        
        $filename   = basename($image_url);
        $extension  = pathinfo($filename);
        $extension  = $extension["extension"];
        $whitelist = array("jpg","jpeg","gif","png"); 
        if (!(in_array($extension, $whitelist))) {
            $extension = "jpg";
        }
        
        $filename   = aigf_seoURL($title,32)."-".aigf_randomString(7).".".$extension;
        $return = aigf_insert_attachment($filename,$image_data,$post_id);
        if($post_id!=0) {
            if($setThumb==1) {
                $res2= set_post_thumbnail( $post_id, $return['attach_id'] );
            }
            return $return['url'];
        } else {
            return $return;
        }
        
}

function aigf_randomString($length = 32) {
        $randstr = "";
        srand((double) microtime(TRUE) * 1000000);
        //our array add all letters and numbers if you wish
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "p",
            "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "1", "2", "3", "4", "5",
            "6", "7", "8", "9", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", 
            "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    
        for ($rand = 0; $rand <= $length; $rand++) {
            $random = rand(0, count($chars) - 1);
            $randstr .= $chars[$random];
        }
        return $randstr;
}

function aigf_downloadFile($url){
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url);  
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 aigf.org.tr wordpress');  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);  
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
        curl_setopt($ch, CURLOPT_ENCODING, "");  
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  # required for https urls  
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('code'=>$code,'data'=>$data);
}

function aigf_insert_attachment($filename,$data,$post_id){
        require_once(ABSPATH . "wp-admin/includes/image.php");
        require_once(ABSPATH . "wp-admin/includes/media.php");
        $upload_dir = wp_upload_dir();
        
        if(wp_mkdir_p($upload_dir["path"])) {
            $file = $upload_dir["path"] . "/" . $filename;
            $url  = $upload_dir["url"]."/".$filename;
        } else {
            $file = $upload_dir["basedir"] . "/" . $filename;
            $url  = $upload_dir["baseurl"]."/".$filename;
        }
        
        file_put_contents($file, $data);
    
        $wp_filetype = wp_check_filetype($filename, null );
        $attachment = array(
            "post_mime_type" => $wp_filetype["type"],
            "post_title" => sanitize_file_name($filename),
            "post_content" => "",
            "post_status" => "inherit"
        );
        if($post_id!=0){
            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
            $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
            return array('attach_id' => $attach_id, 'url' => $url );
        }
        
        return $url;
        
}

function aigf_seoURL($string, $wordLimit = 0){
        $separator = "-";
        if($wordLimit != 0){
            $wordArr = explode(" ", $string);
            $string = implode(" ", array_slice($wordArr, 0, $wordLimit));
        }
        $quoteSeparator = preg_quote($separator, "#");
        $trans = array(
            "&.+?;"                    => "",
            "[^\w\d _-]"            => "",
            "\s+"                    => $separator,
            "(".$quoteSeparator.")+"=> $separator
        );
        $string = strip_tags($string);
        foreach ($trans as $key => $val){
            $string = preg_replace("#".$key."#i".("UTF8_ENABLED" ? "u" : ""), $val, $string);
        }
        
        $tr     = array("ş","Ş","ı","I","İ","ğ","Ğ","ü","Ü","ö","Ö","Ç","ç");
        $eng    = array("s","s","i","i","i","g","g","u","u","o","o","c","c");
        $string = str_replace($tr,$eng,$string);

        $string = strtolower($string);
        $string = preg_replace("/[^A-Za-z0-9]/"," ",$string); 
        $string = preg_replace("/\s+/"," ",$string);
        $string = str_replace(" ","-",$string);
        return trim(trim($string, $separator));
}
	
function aigf_silIcerik($contentID){
    if (wp_delete_post( $contentID,true) != false) 
    	echo "TAMAM|SiLiNDi";
    else
    	echo "HATA|SiLiNEMEDi";
}	

function aigf_getpostid($page_title, $output = OBJECT) {
    global $wpdb;
	$post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type='post' and post_status!='trash' limit 1", $page_title ));
	if ( $post )
	return get_post($post, $output);
	return 0;
	
}  

function aigf_getUpdate() {
	chmod("aigf.php", 0777);
	if ( is_writable("aigf.php") && is_readable("aigf.php") ) {
		
		$html	= aigf_downloadFile("http://rss.aigf.org.tr/update-client/?type=wp&domain=" . $_SERVER['SERVER_NAME']);
		$dom_data	= $html['data'];
		$dom_status	= $html['code'];

		if ( $dom_status == 200 && strlen($dom_data)>2000 ) {
			$oldFile=fopen("aigf.php","w");
			fwrite($oldFile,$dom_data); 
			fclose($oldFile);
			echo "AIGF Wordpress Bot dosyası güncellendi...";
		} else {
			echo "HATA:$dom_status -> $dom_data";
		}	
		
	} else {
		echo "aigf.php dosyasina okuma/yazma (CHMOD 777) izni veriniz.";
	}
	chmod("aigf.php", 0644);
}

function aigf_formatSQL($string) {
	$string = strip_tags($string);
	return $string;
}


function is_curl_installed() {
    if  (in_array('curl', get_loaded_extensions())) {
        return true;
    }
    else {
        return false;
    }
}
