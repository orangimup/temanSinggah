<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); die('ID tidak valid.'); }

$stmt = $koneksi->prepare("
  SELECT p.id, p.status, p.payout_amount,
    p.bank_name, p.account_number,
    p.scheduled_date, p.processed_at,
    p.failure_reason, p.created_at,
    u.nama AS host_nama, u.email AS host_email,
    u.no_hp AS host_phone
  FROM payouts p
  JOIN users u ON u.id = p.host_id
  WHERE p.id = ? LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) { http_response_code(404); die('Payout tidak ditemukan.'); }

function fmt_rupiah(int $n): string {
  return 'Rp ' . number_format($n, 0, ',', '.');
}
function fmt_tgl(?string $d): string {
  if (!$d) return '-';
  $m=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  $ts=strtotime($d);
  return date('j',$ts).' '.$m[(int)date('n',$ts)].' '.date('Y',$ts);
}
function fmt_dt(?string $d): string {
  if (!$d) return '-';
  $m=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  $ts=strtotime($d);
  return date('j',$ts).' '.$m[(int)date('n',$ts)].' '.date('Y',$ts).', '.date('H:i',$ts).' WIB';
}

$font_dir  = __DIR__ . '/fonts/';
$f_reg = $font_dir . 'Inter_18pt-Regular.ttf';
$f_sem = $font_dir . 'Inter_18pt-SemiBold.ttf';
$f_bol = $font_dir . 'Inter_18pt-Bold.ttf';

$status_map = [
  'Completed'   => ['label'=>'Selesai',     'bg'=>[229,243,234],'txt'=>[45,138,87],  'br'=>[185,223,200]],
  'Processing'  => ['label'=>'Diproses',    'bg'=>[229,238,255],'txt'=>[37,99,235],  'br'=>[186,210,254]],
  'Dijadwalkan' => ['label'=>'Dijadwalkan', 'bg'=>[255,243,205],'txt'=>[161,122,0],  'br'=>[250,220,130]],
  'Failed'      => ['label'=>'Gagal',       'bg'=>[255,229,229],'txt'=>[185,28,28],  'br'=>[252,165,165]],
];
$s = $status_map[$p['status']] ?? ['label'=>$p['status'],'bg'=>[240,240,240],'txt'=>[100,100,100],'br'=>[200,200,200]];

/* ── Canvas ── */
$W = 940; $H = 1240;
$img = imagecreatetruecolor($W, $H);

function c($img,$r,$g,$b){ return imagecolorallocate($img,$r,$g,$b); }

$C_BG    = c($img,248,245,241);
$C_WHITE = c($img,255,255,255);
$C_ACC   = c($img,163,56,0);
$C_ACCD  = c($img,139,37,0);
$C_BDR   = c($img,234,216,202);
$C_DIV   = c($img,238,225,215);
$C_DARK  = c($img,31,31,31);
$C_MID   = c($img,122,90,71);
$C_LGT   = c($img,157,141,130);
$C_LBL   = c($img,195,169,153);
$C_IDBG  = c($img,255,244,237);
$C_IDBR  = c($img,240,198,170);
$C_IDTX  = c($img,163,56,0);
$C_OKBG  = c($img,229,243,234);
$C_OKTX  = c($img,45,138,87);
$C_OKBR  = c($img,185,223,200);
$C_HDW   = c($img,255,200,170);
$C_HDW2  = c($img,255,170,120);

/* ── Helpers ── */
function T($img,$sz,$font,$x,$y,$col,$txt){
  // y is TOP of text
  imagettftext($img,$sz,0,(int)$x,(int)($y+$sz),$col,$font,$txt);
}
function TR($img,$sz,$font,$y,$col,$txt,$rx){
  $b=imagettfbbox($sz,0,$font,$txt);
  $tw=$b[2]-$b[0];
  imagettftext($img,$sz,0,(int)($rx-$tw),(int)($y+$sz),$col,$font,$txt);
}
function TW($sz,$font,$txt){
  $b=imagettfbbox($sz,0,$font,$txt);
  return abs($b[2]-$b[0]);
}
function TH($sz,$font,$txt){
  $b=imagettfbbox($sz,0,$font,$txt);
  return abs($b[1]-$b[5]);
}

/* Simple filled rounded rect — no arc tricks, just 3 rects + 4 circles */
function FRR($img,$x1,$y1,$x2,$y2,$r,$col){
  $r=(int)min($r,($x2-$x1)/2,($y2-$y1)/2);
  imagefilledrectangle($img,$x1+$r,$y1,$x2-$r,$y2,$col);
  imagefilledrectangle($img,$x1,$y1+$r,$x2,$y2-$r,$col);
  imagefilledellipse($img,$x1+$r,$y1+$r,$r*2,$r*2,$col);
  imagefilledellipse($img,$x2-$r,$y1+$r,$r*2,$r*2,$col);
  imagefilledellipse($img,$x1+$r,$y2-$r,$r*2,$r*2,$col);
  imagefilledellipse($img,$x2-$r,$y2-$r,$r*2,$r*2,$col);
}

/* ══ BACKGROUND ══ */
imagefilledrectangle($img,0,0,$W,$H,$C_BG);

/* ══ HEADER ══ */
imagefilledrectangle($img,0,0,$W,96,$C_ACC);

// Logo circle
imagefilledellipse($img,64,48,60,60,$C_WHITE);
$logo_c=c($img,163,56,0);
$lw=TW(15,$f_bol,'TS'); 
imagettftext($img,15,0,(int)(64-$lw/2),(int)(48+6),$logo_c,$f_bol,'TS');

// Brand text
T($img,21,$f_bol, 108,16,$C_WHITE,'Teman Singgah');
T($img,11,$f_reg, 108,46,$C_HDW,  'Platform Penginapan Terpercaya');

// Right side
$doc_no='PAY-'.date('Y').'-'.str_pad($p['id'],5,'0',STR_PAD_LEFT);
TR($img,13,$f_bol, 14,$C_WHITE,         'BUKTI PENCAIRAN DANA', $W-36);
TR($img,10,$f_sem, 34,$C_HDW,           'No: '.$doc_no,         $W-36);
TR($img,9, $f_reg, 52,$C_HDW2,          'Dicetak: '.date('d/m/Y H:i').' WIB', $W-36);

/* ══ CARD ══ */
$CX=32; $CY=112; $CX2=$W-32; $CY2=$H-32;
FRR($img,$CX,$CY,$CX2,$CY2,20,$C_WHITE);

// Card border (manual 1px lines)
$lx=$CX+36; $rx=$CX2-36; // content padding

// thin border lines on card edges
imageline($img,$CX+20,$CY,$CX2-20,$CY,$C_BDR);
imageline($img,$CX+20,$CY2,$CX2-20,$CY2,$C_BDR);
imageline($img,$CX,$CY+20,$CX,$CY2-20,$C_BDR);
imageline($img,$CX2,$CY+20,$CX2,$CY2-20,$C_BDR);

$y = $CY + 40;

/* ── ID Payout + Status ── */
T($img,9,$f_bol,$lx,$y,$C_LBL,'ID PAYOUT');
$y+=16;

$id_str='#'.str_pad($p['id'],5,'0',STR_PAD_LEFT);
$id_pw=TW(15,$f_bol,$id_str);
$pill_w=$id_pw+40; $pill_h=44;
FRR($img,$lx,$y,$lx+$pill_w,$y+$pill_h,10,$C_IDBG);
imageline($img,$lx+10,$y,$lx+$pill_w-10,$y,$C_IDBR);
imageline($img,$lx+10,$y+$pill_h,$lx+$pill_w-10,$y+$pill_h,$C_IDBR);
imageline($img,$lx,$y+10,$lx,$y+$pill_h-10,$C_IDBR);
imageline($img,$lx+$pill_w,$y+10,$lx+$pill_w,$y+$pill_h-10,$C_IDBR);
T($img,15,$f_bol,$lx+20,$y+12,$C_IDTX,$id_str);

// Status badge
$s_bgc =c($img,$s['bg'][0],$s['bg'][1],$s['bg'][2]);
$s_txc =c($img,$s['txt'][0],$s['txt'][1],$s['txt'][2]);
$s_brc =c($img,$s['br'][0],$s['br'][1],$s['br'][2]);
$sw=TW(12,$f_bol,$s['label']); $sh=36;
$sbx=$rx-$sw-32; $sby=$y+4;
FRR($img,$sbx,$sby,$sbx+$sw+32,$sby+$sh,18,$s_bgc);
imageline($img,$sbx+18,$sby,$sbx+$sw+14,$sby,$s_brc);
imageline($img,$sbx+18,$sby+$sh,$sbx+$sw+14,$sby+$sh,$s_brc);
imageline($img,$sbx,$sby+18,$sbx,$sby+$sh-18,$s_brc);
imageline($img,$sbx+$sw+32,$sby+18,$sbx+$sw+32,$sby+$sh-18,$s_brc);
T($img,12,$f_bol,$sbx+16,$sby+11,$s_txc,$s['label']);

$y+=$pill_h+28;

/* ── DIVIDER ── */
imagefilledrectangle($img,$lx,$y,$rx,$y+1,$C_DIV);
$y+=24;

/* ── INFO GRID ── */
$col=$lx; $col2=$lx+(($rx-$lx)/2)+14; $rg=50;

function ROW($img,$x,$y,$label,$val,$fb,$fs,$cl,$cd){
  imagettftext($img,9,0,(int)$x,(int)($y+9),$cl,$fb,strtoupper($label));
  imagettftext($img,14,0,(int)$x,(int)($y+27),$cd,$fs,$val);
}

ROW($img,$col, $y,'Nama Host',        $p['host_nama'],              $f_bol,$f_sem,$C_LBL,$C_DARK);
ROW($img,$col2,$y,'Email',            $p['host_email']??'-',        $f_bol,$f_sem,$C_LBL,$C_DARK);
$y+=$rg;
ROW($img,$col, $y,'No. HP',           $p['host_phone']??'-',        $f_bol,$f_sem,$C_LBL,$C_DARK);
ROW($img,$col2,$y,'Bank',             $p['bank_name']?:'-',         $f_bol,$f_sem,$C_LBL,$C_DARK);
$y+=$rg;
ROW($img,$col, $y,'No. Rekening',     $p['account_number']?:'-',    $f_bol,$f_sem,$C_LBL,$C_DARK);
ROW($img,$col2,$y,'Jadwal Transfer',  fmt_tgl($p['scheduled_date']),$f_bol,$f_sem,$C_LBL,$C_DARK);
$y+=$rg;
ROW($img,$col, $y,'Tanggal Diproses', fmt_dt($p['processed_at']),   $f_bol,$f_sem,$C_LBL,$C_DARK);
ROW($img,$col2,$y,'Tanggal Dibuat',   fmt_dt($p['created_at']),     $f_bol,$f_sem,$C_LBL,$C_DARK);
$y+=$rg+12;

/* ── DIVIDER ── */
imagefilledrectangle($img,$lx,$y,$rx,$y+1,$C_DIV);
$y+=24;

/* ── TOTAL ── */
T($img,9,$f_bol,$lx,$y,$C_LBL,'TOTAL PENCAIRAN');
$y+=18;
$nominal=fmt_rupiah((int)$p['payout_amount']);
T($img,32,$f_bol,$lx,$y,$C_ACC,$nominal);

// Lunas badge
$lw2=TW(12,$f_bol,'Lunas')+32;
$lbx=$rx-$lw2; $lby=$y+4;
FRR($img,$lbx,$lby,$rx,$lby+36,18,$C_OKBG);
imageline($img,$lbx+18,$lby,$rx-18,$lby,$C_OKBR);
imageline($img,$lbx+18,$lby+36,$rx-18,$lby+36,$C_OKBR);
T($img,12,$f_bol,$lbx+16,$lby+11,$C_OKTX,'Lunas');

$y+=50;

/* ── FAILURE ── */
if($p['status']==='Failed' && $p['failure_reason']){
  imagefilledrectangle($img,$lx,$y,$rx,$y+1,$C_DIV);
  $y+=16;
  $fbg=c($img,255,237,237);$ftx=c($img,185,28,28);$fbr=c($img,252,165,165);
  FRR($img,$lx,$y,$rx,$y+68,10,$fbg);
  T($img,9,$f_bol,$lx+16,$y+10,$ftx,'ALASAN GAGAL');
  T($img,13,$f_reg,$lx+16,$y+28,$ftx,mb_substr($p['failure_reason'],0,80));
  $y+=84;
}

/* ── TANDA TANGAN ── */
$y+=20;
imagefilledrectangle($img,$lx,$y,$rx,$y+1,$C_DIV);
$y+=24;
T($img,11,$f_reg,$lx,$y,$C_LGT,'Diterbitkan oleh:');
TR($img,11,$f_reg,$y,$C_LGT,'Diterima oleh:',$rx);
$y+=46;
T($img,14,$f_bol,$lx,$y,$C_DARK,'Admin Teman Singgah');
TR($img,14,$f_bol,$y,$C_DARK,$p['host_nama'],$rx);
$y+=22;
T($img,11,$f_reg,$lx,$y,$C_LGT,'Platform Teman Singgah');
TR($img,11,$f_reg,$y,$C_LGT,'Host',$rx);

/* ══ FOOTER ══ */
imagefilledrectangle($img,0,$H-38,$W,$H,$C_ACC);
$ft='Teman Singgah  ·  Dokumen digenerate otomatis  ·  '.date('d/m/Y H:i').' WIB';
$ftw=TW(9,$f_reg,$ft);
imagettftext($img,9,0,(int)(($W-$ftw)/2),(int)($H-12),$C_WHITE,$f_reg,$ft);

/* ══ OUTPUT ══ */
$filename='Payout_'.$doc_no.'_'.date('Ymd').'.png';
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="'.$filename.'"');
imagepng($img,null,6);
imagedestroy($img);
exit;