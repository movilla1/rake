<?php
require_once dirname(__FILE__) . "/../src/rake.php";
use Rake\Rake;

$text = filter_input(INPUT_GET, "t");
$rake = new Rake(dirname(__FILE__) . "/../SmartStoplist.txt");
$keywords_out = "";
if ($text != null) {
  $keywords = $rake->run($text);

  $keywords_to_join = $rake->getSuggestedKeywords();
  $keywords_out = join(" | ", $keywords_to_join);
}
?>
<html>
  <head>
    <title>Test page for the rake algorithm</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container-fluid">
      <div class="header">
        <h2>RAKE algorithm test page</h2>
      </div>
      <div class="row">
        <div class="col-md-4 col-md-offset-4">
          <form method="get" action="" class="inline">
            <div class="form-group">
              <label for="t">Text:</label>
              <input type="text" size="40" name="t" placeholder="Put here the text to extract keywords from" />
              <button class="btn btn-primary" type="submit">Go</button>
            </div>
          </form>
        </div>
      </div>
      <div class="row"><br/></div>
      <div class="row">
        <div class="col-md-2" style="text-align: right">
          <strong>For the text:</strong>
        </div>
        <div class="col-md-9">
          <pre><?=$text?></pre>
        </div>
      </div>
      <div class="row">
        <div class="col-md-2" style="text-align: right">
          <strong>We suggest you to use:</strong>
        </div>
        <div class="col-md-9">
          <pre><?=$keywords_out?></pre>
        </div>
    </div>
    <div class="row"><br/></div>
    <div class="row">
      <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-info">
          <div class="panel-heading">
            <h4>Information on the result </h4>
          </div>
          <div class="panel-body">
            <p>The | character is to simbolize logic OR if your keyword usage script does not understand that, 
            please change it to what fits</p>
            <p> E.G.: (SQL) </p>
            <pre> keywords1 | keywords2 -> keywords LIKE 'keywords1' OR keywords LIKE 'keywords2' </pre>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>