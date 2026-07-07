<?php
function password_strength($password, $user_inputs = array()) {
    $score = 0;
    if (strlen($password) >= 8) $score++;
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;

    $penalty = 0;
    if (preg_match('/(.)\1{2,}/', $password)) $penalty++; // repeat
    $sequences = array('abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789');
    foreach ($sequences as $seq) {
        for ($i = 0; $i < strlen($seq) - 2; $i++) {
            if (strpos($password, substr($seq, $i, 3)) !== false) {
                $penalty++;
                break 2;
            }
        }
    }
    $keyboard_rows = array('qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1234567890');
    foreach ($keyboard_rows as $row) {
        for ($i = 0; $i < strlen($row) - 2; $i++) {
            if (strpos($password, substr($row, $i, 3)) !== false) {
                $penalty++;
                break 2;
            }
        }
    }
    if (!empty($user_inputs)) {
        $lower_pwd = strtolower($password);
        foreach ($user_inputs as $input) {
            $clean = (strpos($input, '@') !== false) ? explode('@', $input)[0] : $input;
            if (strlen($clean) >= 4 && strpos($lower_pwd, strtolower($clean)) !== false) {
                $penalty++;
                break;
            }
        }
    }

    $final_score = max(0, min(4, $score - $penalty));

    if ($final_score <= 1) $label = 'weak';
    elseif ($final_score == 2) $label = 'fair';
    elseif ($final_score == 3) $label = 'good';
    else $label = 'strong';

    return array(
        'score' => $final_score,
        'label' => $label
    );
}