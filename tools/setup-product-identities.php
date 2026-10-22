<?php
require __DIR__.'/bootstrap.php';
echo ProductRepository::advanceIdentityPastHistory()
    ? "Product identity counter advanced beyond historical inventory references.\n"
    : "Product identity counter already safe; no change.\n";
