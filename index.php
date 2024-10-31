<?php

/*

Plugin Name: SayAc

Plugin URI: http://say.ac

Description: Say.Ac WordPress Eklentisi

Author: Savaş Can Altun

Version: 1.0.2

Author URI: http://savascanaltun.com.tr

*/

$eklentidizin = plugins_url();
$paylasim_adresi = get_option('sayac_paylasim');


Class SayAC
{

    public $paylasimadres = NULL;

    public $source = NULL;


    public function veriCek($url)
    {
        $user = getenv('USER_AGENT');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $user);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_REFERER, 'http://www.savascanaltun.com');
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $data = curl_exec($curl);
        curl_close($curl);

        if ($data) return $data; else return false;
    }

    public function __construct($adres)
    {

        $this->source = $this->veriCek($adres);
    }


    public function sayAc()
    {

        $kaynak = $this->source;

        preg_match_all('#<td class="text-right">(.*?)</td>#si', $kaynak, $url);
        preg_match_all('#<table class="table table-striped table-condensed">(.*?)</table>#si', $kaynak, $istatislik);

        $this->tekilhit = $url[0][0];
        $this->cogulhit = $url[0][1];
        $this->istatislik = $istatislik[1][0];
    }

    public function tekilhit()
    {
        $this->sayAc();

        return $this->tekilhit;
    }

    public function cogulhit()
    {
        $this->sayAc();
        return $this->cogulhit;

    }

    public function istatislik()
    {
        $this->sayAc();
        return $this->istatislik;
    }

    public function ontekil()
    {
        $kaynak = $this->source;
        preg_match_all('#<td class="text-right">(.*?)</td>#si', $kaynak, $url);
        $str = "";
        for ($i = 0; $i < 20; $i = $i + 2) {
            $cogul = $url[0][$i];
            $cogul = str_replace(".", "", $cogul);
            if (empty($str)) {
                $str = $str . $cogul;
            } else {
                $str = $str . "," . $cogul;
            }

        }
        return $str;
    }


    public function oncogul()
    {
        $kaynak = $this->source;
        preg_match_all('#<td class="text-right">(.*?)</td>#si', $kaynak, $url);
        $str = "";
        for ($i = 1; $i < 20; $i = $i + 2) {
            $cogul = $url[0][$i];
            $cogul = str_replace(".", "", $cogul);
            if (empty($str)) {
                $str = $str . $cogul;
            } else {
                $str = $str . "," . $cogul;
            }

        }
        return $str;
    }


    public function ontarih()
    {
        $str = "";
        for ($i = 0; $i < 10; $i++) {


            if (empty($str)) {
                $str = $str . '"' . date('d.m.Y', strtotime("-" . $i . " day")) . '"';
            } else {
                $str = $str . "," . '"' . date('d.m.Y', strtotime("-" . $i . " day")) . '"';
            }


        }
        return $str;


    }


}


add_action('wp_dashboard_setup', 'sayAc');
add_action("admin_menu", "SayAcSayfa");
add_action('wp_dashboard_setup', 'javascript_include');
function javascript_include()
{


    wp_register_script('chart-js', plugin_dir_url(__FILE__) . 'Chart.js', '', null, '');

    wp_enqueue_script('chart-js');


}


function sayAc()
{
    global $wp_meta_boxes;

    wp_add_dashboard_widget('custom_help_widget', 'SayAc son 10 gün raporu', 'sayAcIstatislik', 'side', 'high');


}

function SayAcSayfa()
{
    add_menu_page("Ana Sayfa", "Say.ac", 10, "SayAcAyarlari", "SayAcAyarlari", NULL, "145");

    add_action('admin_init', 'ayarlari_kayit_et');

}

function ayarlari_kayit_et()
{

    register_setting('say-ac', 'sayac_paylasim');

}


function SayAcAyarlari()
{
    ?>

    <div style="margin:25px;">
        <h3>SayAc Paylaşım Adresiniz ? </h3>
        <hr/>
        <form method="post" action="options.php">
            <?php settings_fields('say-ac'); ?>
            <?php do_settings_sections('say-ac');
            $say_ac_paylasim_url = get_option('sayac_paylasim');

            ?>
            <input name="sayac_paylasim" id="sayac_paylasim" type="text" value="<?php echo $say_ac_paylasim_url; ?>"
                   style="width:100%;"/>
            <?php submit_button(); ?>
        </form>

    </div>
    <div style="font-size:9px; margin-bottom:10px;">Say.ac için <a href="http://www.savascanaltun.com.tr/">Savas Can
            Altun</a> tarafından hazırlanmıştır.
    </div>
<?php
}

function sayAcIstatislik()
{
    global $paylasim_adresi;
    $class = new SayAC($paylasim_adresi);
    $ontekil = $class->ontekil();
    $ontarih = $class->ontarih();
    ?>

    <div style="width: 88%;height: 25%;margin:0 auto;">
        <canvas id="istatislik"></canvas>
    </div>

    <script type="text/javascript">
        var randomScalingFactor = function () {
            return Math.round(Math.random() * 20000)
        };

        var barChartData = {
            labels: [<?php echo $ontarih ?>],
            datasets: [
                {
                    fillColor: "rgba(77,116,150,1)",
                    highlightFill: "rgba(220,220,220,0.75)",
                    highlightStroke: "rgba(220,220,220,1)",
                    data: [ <?php echo strip_tags($ontekil) ?>]
                }
            ]

        }
        window.onload = function () {
            var ctx = document.getElementById("istatislik").getContext("2d");
            window.myBar = new Chart(ctx).Bar(barChartData, {
                responsive: true
            });
        }

    </script>
    <?php
    $eklentidizin = plugins_url();

}


?>