<?php

  $labelStyle = "font-family: sans-serif; font-size: 14px; font-weight: bold; white-space: nowrap; vertical-align: top; padding: 2px;";
  $valueStyle = "font-family: sans-serif; font-size: 14px; vertical-align: top; padding: 2px;";

  if (isset($salutation)) {
    $salutation = $salutation === 'male' ? 'Mr.' : 'Mrs.';
  } else {
    $salutation = '';
  }
?>
<html>
  <head></head>
  <body>
    <table style="border: none;">
      <tr>
        <td style="<?= $labelStyle ?>">Time</td>
        <td style="<?= $valueStyle ?>"><?= @$__date__ ?> <?= @$__time__ ?></td>
      </tr>
      <tr>
        <td style="<?= $labelStyle ?>">Salutation</td>
        <td style="<?= $valueStyle ?>"><?= @$salutation ?></td>
      </tr>
      <tr>
        <td style="<?= $labelStyle ?>">Name</td>
        <td style="<?= $valueStyle ?>"><?= @$prename ?> <?= @$surname ?></td>
      </tr>
      <tr>
        <td style="<?= $labelStyle ?>">Message</td>
        <td style="<?= $valueStyle ?>"><?= nl2br(@$message) ?></td>
      </tr>
    </table>
  </body>
</html>




