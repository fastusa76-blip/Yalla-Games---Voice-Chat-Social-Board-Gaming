<?php
declare(strict_types=1);

$base = getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8080';
$adminKey = getenv('ADMIN_API_KEY') ?: '';
function call_api(string $url, ?array $body = null, string $adminKey = ''): array {
    $headers = "Accept: application/json\r\n";
    if ($body !== null) $headers .= "Content-Type: application/json\r\n";
    if ($adminKey !== '') $headers .= "Authorization: Bearer {$adminKey}\r\n";
    $context = stream_context_create(['http' => [
        'method' => $body === null ? 'GET' : 'POST',
        'header' => $headers,
        'content' => $body === null ? '' : json_encode($body),
        'ignore_errors' => true,
        'timeout' => 10,
    ]]);
    $raw = file_get_contents($url, false, $context);
    if ($raw === false) throw new RuntimeException('HTTP request failed: ' . $url);
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new RuntimeException('API did not return JSON: ' . $url . ' Response: ' . $raw);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $m)) $status = (int)$m[1];
    return [$status, $data];
}
function expect(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS: {$message}\n"; }

[$status,$health]=call_api($base.'/api/index.php?action=health');
expect($status===200 && ($health['ok']??false)===true, 'health and database connection');
[$status,$chat]=call_api($base.'/api/index.php?action=chat',['message'=>'Hello Yalla']);
expect($status===200 && isset($chat['reply']), 'chatbot API returns a reply');
[$status,$bad]=call_api($base.'/api/index.php?action=orders',['name'=>'','phone'=>'bad','game'=>'','quantity'=>0]);
expect($status===422 && ($bad['ok']??true)===false, 'invalid order data is rejected');
[$status,$created]=call_api($base.'/api/index.php?action=orders',[
    'name'=>'Automated Test Customer','phone'=>'+923001234567','game'=>'Automated Test Game','quantity'=>2,'email'=>'test@example.com','notes'=>'CI test order'
]);
expect($status===201 && ($created['order']['status']??'')==='pending', 'valid order is stored as pending');
$ref=$created['order']['reference']; $token=$created['order']['access_token'];
[$status,$lookup]=call_api($base.'/api/index.php?action=order-status',['reference'=>$ref,'token'=>$token]);
expect($status===200 && ($lookup['order']['status']??'')==='pending', 'customer can check private order status');
[$status,$forbidden]=call_api($base.'/api/index.php?action=admin-orders');
expect($status===401, 'admin endpoint rejects unauthenticated requests');
[$status,$orders]=call_api($base.'/api/index.php?action=admin-orders',null,$adminKey);
expect($status===200 && count($orders['orders']??[])>0, 'admin can list orders');
[$status,$updated]=call_api($base.'/api/index.php?action=admin-update',['reference'=>$ref,'status'=>'confirmed','admin_note'=>'CI confirmation test'],$adminKey);
expect($status===200 && ($updated['status']??'')==='confirmed', 'admin can confirm an order');
[$status,$confirmed]=call_api($base.'/api/index.php?action=order-status',['reference'=>$ref,'token'=>$token]);
expect($status===200 && ($confirmed['order']['status']??'')==='confirmed', 'customer sees confirmed status after admin update');
[$status,$wrongToken]=call_api($base.'/api/index.php?action=order-status',['reference'=>$ref,'token'=>str_repeat('0',64)]);
expect($status===404, 'private order token prevents unauthorized status lookup');
echo "All integration checks passed.\n";
