</div>
<?php
//$db->close();
echo '<div class="footer">'; 
if(isset($_COOKIE["HomeEgregius"])) echo '<form method="post" action="logout.php"><input type="submit" name="logout" value="Uitloggen" class="abutton settings gradient"/></form>';
?>
</div>
</body>
</html>