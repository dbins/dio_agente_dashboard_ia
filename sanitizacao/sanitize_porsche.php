<?php
/**
 * Sanitização de planilha de vendas Porsche usando PHPExcel.
 * Requer PHPExcel instalado e incluído no projeto.
 * Uso: php sanitize_porsche.php [entrada.xlsx] [saida.xlsx]
 */

require_once __DIR__ . '/Classes/PHPExcel.php'; // Ajuste para o caminho da sua instalação.

const INVALID_VALUE = 'INVALID';

function safeDate($y, $m, $d) {
    if (!checkdate((int)$m, (int)$d, (int)$y)) return INVALID_VALUE;
    return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
}

function sanitizeDate($value) {
    if ($value === null || $value === '') return INVALID_VALUE;
    if ($value instanceof DateTimeInterface) return $value->format('Y-m-d');

    // Datas numéricas do Excel são convertidas antes de chegarem aqui.
    $s = trim((string)$value);
    if ($s === '') return INVALID_VALUE;
    if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $s, $m))
        return safeDate($m[1], $m[2], $m[3]);
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $s, $m))
        return safeDate($m[3], $m[1], $m[2]);
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2})$/', $s, $m)) {
        $yy = (int)$m[3]; $year = $yy < 70 ? 2000 + $yy : 1900 + $yy;
        return safeDate($year, $m[1], $m[2]);
    }
    $months = ['jan'=>1,'january'=>1,'feb'=>2,'february'=>2,'mar'=>3,'march'=>3,
        'apr'=>4,'april'=>4,'may'=>5,'jun'=>6,'june'=>6,'jul'=>7,'july'=>7,
        'aug'=>8,'august'=>8,'sep'=>9,'sept'=>9,'september'=>9,'oct'=>10,'october'=>10,
        'nov'=>11,'november'=>11,'dec'=>12,'december'=>12];
    if (preg_match('/^([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?[,\s]+(\d{4})$/i', $s, $m)) {
        $mon = strtolower(rtrim($m[1], '.'));
        if (isset($months[$mon])) return safeDate($m[3], $months[$mon], $m[2]);
    }
    return INVALID_VALUE;
}

$CANONICAL_MODELS = [
    '911 Carrera','911 Carrera S','911 Carrera GTS','911 Turbo','911 Turbo S','911 GT3','911 GT3 RS',
    '911 Dakar','911 Targa 4','911 Targa 4S','718 Cayman','718 Cayman S','718 Cayman GT4 RS',
    '718 Boxster','718 Boxster GTS','718 Spyder RS','Cayenne','Cayenne S','Cayenne Coupe',
    'Cayenne E-Hybrid','Cayenne Turbo','Cayenne Turbo GT','Macan','Macan S','Macan T','Macan GTS',
    'Macan Electric','Panamera','Panamera 4','Panamera 4S','Panamera Turbo','Panamera Turbo S',
    'Panamera 4 E-Hybrid','Taycan','Taycan 4S','Taycan GTS','Taycan Turbo','Taycan Turbo S','Taycan Cross Turismo'
];
function smartTitle($s) {
    $keep = ['GT','GTS','RS','GT3','GT4','S','T','4','4S','USA'];
    $out = [];
    foreach (preg_split('/\s+/', trim($s)) as $part) {
        if (in_array(strtoupper($part), $keep, true)) $out[] = strtoupper($part);
        elseif (strpos($part, '-') !== false) $out[] = implode('-', array_map('ucfirst', explode('-', strtolower($part))));
        else $out[] = ucfirst(strtolower($part));
    }
    return implode(' ', $out);
}
function sanitizeModel($value) {
    global $CANONICAL_MODELS;
    if ($value === null) return INVALID_VALUE;
    $s = trim(preg_replace('/\s+/', ' ', (string)$value));
    if ($s === '') return INVALID_VALUE;
    foreach ($CANONICAL_MODELS as $model) if (strcasecmp($s, $model) === 0) return $model;
    return smartTitle($s);
}

$WORDS_TO_NUM = ['zero'=>0,'one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6,'seven'=>7,
    'eight'=>8,'nine'=>9,'ten'=>10,'eleven'=>11,'twelve'=>12,'thirteen'=>13,'fourteen'=>14,
    'fifteen'=>15,'sixteen'=>16,'seventeen'=>17,'eighteen'=>18,'nineteen'=>19,'twenty'=>20,
    'thirty'=>30,'forty'=>40,'fifty'=>50,'sixty'=>60,'seventy'=>70,'eighty'=>80,'ninety'=>90,
    'hundred'=>100,'thousand'=>1000];
function wordsToInt($text) {
    global $WORDS_TO_NUM;
    preg_match_all('/[a-z]+/i', strtolower($text), $matches); $tokens = $matches[0];
    if (!$tokens) return null;
    foreach ($tokens as $t) if (!array_key_exists($t, $WORDS_TO_NUM)) return null;
    $allSmall = true; foreach ($tokens as $t) if ($WORDS_TO_NUM[$t] >= 100) $allSmall = false;
    if ($allSmall) {
        $nums = array_map(function($t) use ($WORDS_TO_NUM) { return $WORDS_TO_NUM[$t]; }, $tokens);
        $groups = []; $i = 0; $n = count($nums);
        while ($i < $n) {
            if ($i + 1 < $n && $nums[$i] >= 20 && $nums[$i] % 10 === 0 && $nums[$i+1] < 10) {
                $groups[] = $nums[$i] + $nums[$i+1]; $i += 2;
            } else { $groups[] = $nums[$i]; $i++; }
        }
        if (count($groups) === 2) return $groups[0] * 100 + $groups[1];
        if (count($groups) === 1) return $groups[0];
    }
    $total = 0; $current = 0;
    foreach ($tokens as $t) {
        $v = $WORDS_TO_NUM[$t];
        if ($v === 100) $current = max(1, $current) * 100;
        elseif ($v === 1000) { $current = max(1, $current) * 1000; $total += $current; $current = 0; }
        else $current += $v;
    }
    return $total + $current;
}
function sanitizeYear($value) {
    if ($value === null || $value === '') return INVALID_VALUE;
    if (is_numeric($value) && !is_string($value)) { $y = (int)$value; return ($y >= 1990 && $y <= 2035) ? (string)$y : INVALID_VALUE; }
    $s = trim((string)$value); if ($s === '') return INVALID_VALUE;
    if (preg_match('/^\d{4}$/', $s)) { $y=(int)$s; return ($y>=1990 && $y<=2035)?(string)$y:INVALID_VALUE; }
    if (preg_match('/^(\d{2})\s*[-\s.]\s*(\d{2})$/', $s, $m)) { $y=(int)($m[1].$m[2]); return ($y>=1990 && $y<=2035)?(string)$y:INVALID_VALUE; }
    if (preg_match('/[A-Za-z]/', $s)) { $n=wordsToInt($s); if ($n!==null && $n>=1990 && $n<=2035) return (string)$n; }
    return INVALID_VALUE;
}

function parseTextualNumber($s) {
    global $WORDS_TO_NUM;
    preg_match_all('/[a-z]+/i', strtolower($s), $matches); $tokens=$matches[0];
    if (!$tokens) return null;
    foreach ($tokens as $t) if (!isset($WORDS_TO_NUM[$t]) && $t !== 'million') return null;
    $total=0; $current=0;
    foreach ($tokens as $t) {
        if ($t==='million') { $current=max(1,$current)*1000000; $total += $current; $current=0; }
        elseif ($t==='hundred') $current=max(1,$current)*100;
        elseif ($t==='thousand') { $current=max(1,$current)*1000; $total += $current; $current=0; }
        else $current += $WORDS_TO_NUM[$t];
    }
    return (float)($total+$current);
}
function parseNumericPrice($s) {
    $s=trim($s); if ($s==='') return null;
    $dot=strpos($s,'.')!==false; $comma=strpos($s,',')!==false;
    if ($dot && $comma) {
        if (strrpos($s,',') > strrpos($s,'.')) $s=str_replace(',', '.', str_replace('.', '', $s));
        else $s=str_replace(',', '', $s);
    } elseif ($comma) {
        $parts=explode(',', $s); $after=end($parts); preg_match_all('/\d/', $after, $d);
        if (substr_count($s, ',')>1 || count($d[0])!==2) $s=str_replace(',', '', $s); else $s=str_replace(',', '.', $s);
    } elseif ($dot) {
        $parts=explode('.', $s); $after=end($parts); preg_match_all('/\d/', $after, $d);
        if (substr_count($s, '.')>1 || count($d[0])!==2) $s=str_replace('.', '', $s);
    }
    return is_numeric($s) ? (float)$s : null;
}
function sanitizePrice($value) {
    if ($value===null || $value==='') return INVALID_VALUE;
    if (is_numeric($value) && !is_string($value)) return number_format((float)$value, 2, '.', '');
    $s=trim((string)$value); if ($s==='') return INVALID_VALUE;
    $cleaned=preg_replace('/\b(usd|dollars?)\b/i', ' ', str_replace('$', ' ', $s)); $cleaned=trim($cleaned);
    if (preg_match('/^[A-Za-z\s]+$/', $cleaned)) { $n=parseTextualNumber($cleaned); return $n===null?INVALID_VALUE:number_format($n,2,'.',''); }
    $multiplier=1.0;
    if (preg_match('/([0-9.,]+)\s*k\b/i', $cleaned, $m)) { $num=$m[1]; $multiplier=1000.0; }
    elseif (preg_match('/[0-9.,]+/', $cleaned, $m)) $num=$m[0];
    else return INVALID_VALUE;
    $n=parseNumericPrice($num); return $n===null?INVALID_VALUE:number_format($n*$multiplier,2,'.','');
}
function sanitizeMileage($value) {
    if ($value===null || $value==='') return INVALID_VALUE;
    if (is_numeric($value) && !is_string($value)) return (string)(int)round((float)$value);
    $s=trim((string)$value); if ($s==='') return INVALID_VALUE;
    $low=strtolower($s);
    if (in_array($low, ['new','new car','zero miles','zero','0 mi','0 miles','0mi'], true)) return '0';
    $isKm=(bool)(preg_match('/\bkm\b/i',$low) || strpos($low,'km')===0);
    if (!preg_match('/\d/', $s)) {
        $textual=trim(preg_replace('/\b(mi|miles|mile|km|kilometers|kilometres)\b/i',' ',$low));
        $n=parseTextualNumber($textual); if ($n===null) return INVALID_VALUE;
        return (string)(int)round($n*($isKm?0.621371:1));
    }
    if (!preg_match('/[0-9][0-9.,]*/', $s, $m)) return INVALID_VALUE;
    $n=parseNumericPrice($m[0]); if ($n===null) return INVALID_VALUE;
    if ($isKm) $n*=0.621371;
    return (string)(int)round($n);
}
function normalizeTextKey($s) {
    $s=strtolower(trim($s));
    if (function_exists('iconv')) { $converted=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if ($converted!==false) $s=$converted; }
    $s=preg_replace('/[^a-z0-9]+/i',' ',$s); return trim(preg_replace('/\s+/',' ',$s));
}
function titleWords($s) { return implode(' ', array_map('ucfirst', explode(' ', $s))); }
function sanitizePayment($value) {
    if ($value===null || trim((string)$value)==='') return INVALID_VALUE;
    $key=normalizeTextKey((string)$value);
    $lookup=['credit card'=>'Credit Card','creditcard'=>'Credit Card','credit'=>'Credit Card','credit card payment'=>'Credit Card',
        'debit card'=>'Debit Card','debitcard'=>'Debit Card','bank transfer'=>'Bank Transfer','banktransfer'=>'Bank Transfer','bank'=>'Bank Transfer',
        'wire transfer'=>'Wire Transfer','wiretransfer'=>'Wire Transfer','wire'=>'Wire Transfer','bank wire'=>'Wire Transfer','wire bank'=>'Wire Transfer',
        'financing'=>'Financing','finance'=>'Financing','financing plan'=>'Financing','lease'=>'Lease','leasing'=>'Lease','lease plan'=>'Lease',
        'cash'=>'Cash','cash payment'=>'Cash','ach'=>'ACH Payment','ach payment'=>'ACH Payment','crypto'=>'Crypto Payment',
        'crypto payment'=>'Crypto Payment','cryptocurrency'=>'Crypto Payment'];
    return isset($lookup[$key])?$lookup[$key]:titleWords($key);
}
function sanitizeCity($value) {
    if ($value===null) return INVALID_VALUE;
    $s=trim(preg_replace('/\s+/', ' ', (string)$value)); if ($s==='') return INVALID_VALUE;
    $out=[];
    foreach (explode(' ', $s) as $p) {
        if (in_array(strtolower($p), ['st','st.'], true)) $out[]='St.';
        elseif (in_array(strtolower($p), ['mt','mt.'], true)) $out[]='Mt.';
        elseif (strpos($p,'-')!==false) $out[]=implode('-',array_map('ucfirst',explode('-',strtolower($p))));
        else $out[]=ucfirst(strtolower($p));
    }
    return implode(' ', $out);
}
function sanitizeState($value) {
    if ($value===null) return INVALID_VALUE;
    $s=trim((string)$value); if ($s==='') return INVALID_VALUE;
    $states=['alabama'=>'AL','alaska'=>'AK','arizona'=>'AZ','arkansas'=>'AR','california'=>'CA','colorado'=>'CO','connecticut'=>'CT','delaware'=>'DE',
        'florida'=>'FL','georgia'=>'GA','hawaii'=>'HI','idaho'=>'ID','illinois'=>'IL','indiana'=>'IN','iowa'=>'IA','kansas'=>'KS','kentucky'=>'KY',
        'louisiana'=>'LA','maine'=>'ME','maryland'=>'MD','massachusetts'=>'MA','michigan'=>'MI','minnesota'=>'MN','mississippi'=>'MS','missouri'=>'MO',
        'montana'=>'MT','nebraska'=>'NE','nevada'=>'NV','new hampshire'=>'NH','new jersey'=>'NJ','new mexico'=>'NM','new york'=>'NY',
        'north carolina'=>'NC','north dakota'=>'ND','ohio'=>'OH','oklahoma'=>'OK','oregon'=>'OR','pennsylvania'=>'PA','rhode island'=>'RI',
        'south carolina'=>'SC','south dakota'=>'SD','tennessee'=>'TN','texas'=>'TX','utah'=>'UT','vermont'=>'VT','virginia'=>'VA','washington'=>'WA',
        'west virginia'=>'WV','wisconsin'=>'WI','wyoming'=>'WY','district of columbia'=>'DC'];
    $codes=array_values($states); $upper=strtoupper($s);
    if (in_array($upper,$codes,true)) return $upper;
    $key=strtolower(preg_replace('/\s+/',' ',$s)); return isset($states[$key])?$states[$key]:INVALID_VALUE;
}
function sanitizeDelivery($value) {
    if ($value===null || trim((string)$value)==='') return INVALID_VALUE;
    $key=normalizeTextKey((string)$value);
    $lookup=['delivered'=>'Delivered','deliverd'=>'Delivered','pending'=>'Pending','in transit'=>'In Transit','intransit'=>'In Transit',
        'cancelled'=>'Cancelled','canceled'=>'Cancelled','awaiting delivery'=>'Awaiting Delivery','awaiting pickup'=>'Awaiting Pickup',
        'pending approval'=>'Pending Approval','pending review'=>'Pending Review','shipped'=>'Shipped','awaiting review'=>'Awaiting Review'];
    return isset($lookup[$key])?$lookup[$key]:titleWords($key);
}

$pipeline = [
    ['sale_id', null, null], ['sale_date','SaleDateSanitized','sanitizeDate'],
    ['customer_name',null,null], ['porsche_model','PorscheModelSanitized','sanitizeModel'],
    ['model_year','ModelYearSanitized','sanitizeYear'], ['sale_price','SalesPriceSanitized','sanitizePrice'],
    ['vehicle_mileage','VehicleMileageSanitized','sanitizeMileage'], ['payment_method','PayMethodSanitized','sanitizePayment'],
    ['city','CitySanitized','sanitizeCity'], ['state','StateSanitized','sanitizeState'],
    ['salesperson',null,null], ['delivery_status','DeliveryStatusSanitized','sanitizeDelivery']
];

$inputPath = isset($argv[1]) ? $argv[1] : __DIR__ . '/porsche_database.xlsx';
$outputPath = isset($argv[2]) ? $argv[2] : __DIR__ . '/porsche_database_sanitized.xlsx';
if (!is_file($inputPath)) { fwrite(STDERR, "Arquivo de entrada não encontrado: {$inputPath}\n"); exit(1); }

try {
    $reader = PHPExcel_IOFactory::createReaderForFile($inputPath);
    $reader->setReadDataOnly(false);
    $inputBook = $reader->load($inputPath);
    $inputSheet = $inputBook->getActiveSheet();
    $highestRow = $inputSheet->getHighestRow();
    $highestCol = PHPExcel_Cell::columnIndexFromString($inputSheet->getHighestColumn());
    if ($highestRow < 1) throw new RuntimeException('A planilha está vazia.');

    $headerMap=[];
    for ($c=0; $c<$highestCol; $c++) {
        $v=$inputSheet->getCellByColumnAndRow($c,1)->getValue();
        $headerMap[trim((string)$v)]=$c;
    }
    $missing=[];
    foreach ($pipeline as $item) if (!array_key_exists($item[0],$headerMap)) $missing[]=$item[0];
    if ($missing) throw new RuntimeException('Colunas obrigatórias ausentes: '.implode(', ',$missing));

    $outputBook = new PHPExcel();
    $outputSheet = $outputBook->getActiveSheet();
    $outputSheet->setTitle('Sanitized');
    $outHeader=[];
    foreach ($pipeline as $item) { $outHeader[]=$item[0]; if ($item[1]!==null) $outHeader[]=$item[1]; }
    foreach ($outHeader as $i=>$name) {
        $cell=PHPExcel_Cell::stringFromColumnIndex($i).'1';
        $outputSheet->setCellValueExplicit($cell,$name,PHPExcel_Cell_DataType::TYPE_STRING);
        $color=(substr($name,-9)==='Sanitized')?'065F46':'1F2937';
        $outputSheet->getStyle($cell)->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill'=>['type'=>PHPExcel_Style_Fill::FILL_SOLID,'color'=>['rgb'=>$color]]]);
        $outputSheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($i))->setWidth(max(14,strlen($name)+2));
    }
    $invalidCounts=[];
    for ($r=2; $r<=$highestRow; $r++) {
        $outCol=0;
        foreach ($pipeline as $item) {
            $src=$item[0]; $sanName=$item[1]; $fn=$item[2];
            $cell=$inputSheet->getCellByColumnAndRow($headerMap[$src],$r);
            $raw=$cell->getValue();
            if ($src==='sale_date' && PHPExcel_Shared_Date::isDateTime($cell) && is_numeric($raw)) {
                try { $raw=PHPExcel_Shared_Date::ExcelToPHPObject($raw); } catch (Exception $e) { /* sanitizer retorna INVALID */ }
            }
            $outCell=PHPExcel_Cell::stringFromColumnIndex($outCol++).$r;
            if ($raw===null) $outputSheet->setCellValue($outCell,null);
            elseif ($raw instanceof DateTimeInterface) $outputSheet->setCellValue($outCell,$raw->format('Y-m-d'));
            else $outputSheet->setCellValueExplicit($outCell,(string)$raw,PHPExcel_Cell_DataType::TYPE_STRING);
            if ($sanName!==null && $fn!==null) {
                $clean=call_user_func($fn,$raw);
                if ($clean===INVALID_VALUE) $invalidCounts[$sanName]=isset($invalidCounts[$sanName])?$invalidCounts[$sanName]+1:1;
                $cleanCell=PHPExcel_Cell::stringFromColumnIndex($outCol++).$r;
                $outputSheet->setCellValueExplicit($cleanCell,(string)$clean,PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
    }
    $outputSheet->freezePane('A2');
    $writer=PHPExcel_IOFactory::createWriter($outputBook,'Excel2007');
    $writer->save($outputPath);
    echo "OK -> {$outputPath}\n";
    if ($invalidCounts) {
        echo "Quantidade de valores INVALID por coluna:\n";
        ksort($invalidCounts);
        foreach ($invalidCounts as $column=>$count) echo "  {$column}: {$count}\n";
    } else echo "Nenhum valor INVALID foi produzido.\n";
} catch (Exception $e) {
    fwrite(STDERR, 'Erro: '.$e->getMessage()."\n"); exit(1);
}
