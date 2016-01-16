<?php
require_once "header.php";
if($authenticated != true) {
	header("Location: index.php");
	die("Redirecting to index.php"); 
} else {
if(!empty($_POST['newusername']))
    {
        if(empty($_POST['newusername'])) die("Please enter a username.");
        if(empty($_POST['newpassword'])) die("Please enter a password.");
        $usernaam = $_POST['newusername'];
		$sql = "SELECT 1 FROM users WHERE username like '$usernaam'";
        if(!$result = $db->query($sql)){ echo('There was an error running the query [' . $db->error . ']');}
		$row = $result->fetchArray();
        if($row) echo("<br/><font size='+1'><font color=\"#FF0000\">Deze gebruikersnaam bestaat al.</font>");
		$salt = dechex(mt_rand(0, 2147483647)) . dechex(mt_rand(0, 2147483647));
    	$passwoord = hash('sha256', $_POST['newpassword'] . $salt);
    	for($round = 0; $round < 65536; $round++) {$passwoord = hash('sha256', $passwoord . $salt);}
        $sql = "INSERT INTO users (username,password,salt) VALUES ('$usernaam','$passwoord','$salt')";
    	if(!$result = $db->exec($sql)){ echo('There was an error running the query [' . $db->error . ']');}
	}
?>
<div class="item gradient">
<h2>Registreer nieuwe gebruiker</h2>
<form action="#" method="post">
    Gebruikersnaam:<br />
    <input type="text" name="newusername" value="" />
    <br /><br />
    Wactwoord:<br />
    <input type="password" name="newpassword" value="" />
    <br /><br />
	<input type="hidden" name="gebruikers" value="Gebruikers" class="abutton settings gradient"/>
    <input type="submit" value="Registreer nieuwe gebruiker" class="gradient abutton settings"/>
</form>
</div>
<?php
}