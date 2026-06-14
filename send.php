<?php
// Cílová emailová adresa
define('TO_EMAIL', 'buildtradepartners@email.cz');
define('TO_NAME',  'Build & Trade Partners');

// Povolené hlavičky pro AJAX (stejná doména)
header('Content-Type: application/json; charset=utf-8');

// Přijmeme jen POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metoda není povolena.']);
    exit;
}

// Načtení a ošetření vstupů
$jmeno   = trim(strip_tags($_POST['jmeno']   ?? ''));
$telefon = trim(strip_tags($_POST['telefon'] ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$zajem   = trim(strip_tags($_POST['zajem']   ?? ''));
$zprava  = trim(strip_tags($_POST['zprava']  ?? ''));

// Základní validace
if ($jmeno === '' || $email === '' || $zprava === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Vyplňte prosím jméno, e-mail a zprávu.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Zadejte platnou e-mailovou adresu.']);
    exit;
}

// Ochrana proti header injection
$jmeno   = preg_replace('/[\r\n]/', '', $jmeno);
$email   = preg_replace('/[\r\n]/', '', $email);
$telefon = preg_replace('/[\r\n]/', '', $telefon);
$zajem   = preg_replace('/[\r\n]/', '', $zajem);

// Sestavení těla emailu
$telo  = "Nová zpráva z kontaktního formuláře\n";
$telo .= str_repeat('─', 40) . "\n\n";
$telo .= "Jméno:    {$jmeno}\n";
$telo .= "E-mail:   {$email}\n";
$telo .= "Telefon:  " . ($telefon ?: '–') . "\n";
$telo .= "Zájem o:  " . ($zajem   ?: '–') . "\n\n";
$telo .= "Zpráva:\n{$zprava}\n\n";
$telo .= str_repeat('─', 40) . "\n";
$telo .= "Odesláno: " . date('d.m.Y H:i') . "\n";

// Hlavičky emailu
$predmet  = "Nová poptávka od {$jmeno}";
$hlavicky  = "From: =?UTF-8?B?" . base64_encode(TO_NAME) . "?= <noreply@buildtradepartners.cz>\r\n";
$hlavicky .= "Reply-To: {$jmeno} <{$email}>\r\n";
$hlavicky .= "MIME-Version: 1.0\r\n";
$hlavicky .= "Content-Type: text/plain; charset=UTF-8\r\n";
$hlavicky .= "Content-Transfer-Encoding: base64\r\n";

$predmet_encoded = '=?UTF-8?B?' . base64_encode($predmet) . '?=';

$odeslano = mail(TO_EMAIL, $predmet_encoded, base64_encode($telo), $hlavicky);

if ($odeslano) {
    echo json_encode(['ok' => true, 'message' => 'Zpráva odeslána. Ozveme se vám co nejdříve.']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Zprávu se nepodařilo odeslat. Zkuste to prosím znovu nebo nás kontaktujte telefonicky.']);
}
