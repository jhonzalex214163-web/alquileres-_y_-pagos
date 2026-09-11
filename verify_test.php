<?php
$hash = '$2y$10$N6EZ6RPK3Gxi4399MXrh3Oe9wKNoWHW3mi4QniOW00zJwvxASbT.y';
echo 'Hash: ' . $hash . "\n";
echo 'Verify password123: ' . (password_verify('password123', $hash) ? 'true' : 'false') . "\n";
echo 'Verify wrongpass: ' . (password_verify('wrongpass', $hash) ? 'true' : 'false') . "\n";
