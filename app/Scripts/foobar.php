<?php
$result = '';
for ($x = 1; $x <= 100; $x++)
    {
        $output = '';
        if ($x % 3 === 0)
        {
            $output .= 'foo';
        }
        if ($x % 5 === 0)
        {
            $output .= 'bar';
        }
        $result .= ($output !== '') ? $output : $x;
        if ($x !== 100)
        {
            $result .= ', ';
        }
    }
echo $result;
