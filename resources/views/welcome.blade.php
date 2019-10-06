<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>下载m3u8</title>
</head>
<body>
<form method="post">
    视频名称：
    <input type="text" name="video_name" name="video_name" value="<?=$_POST['video_name'] ?? "" ?>">
    m3u8文件地址：
    <textarea rows="15" cols="60" name="m3u8_url"><?=$_POST['m3u8_url'] ?? "" ?></textarea>
    {{ csrf_field() }}
    <input type="submit" value="提交">
</form>
</body>
</html>