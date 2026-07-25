<?php
// Allow opening http://localhost/ihsa/ when using XAMPP's default htdocs root.
header('Location: public/', true, 302);
exit;
