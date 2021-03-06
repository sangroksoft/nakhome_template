<?php
include_once('./_common.php');

$returnVal = "";

//===================== 어선정보가져옴 ========================
$s_idx = clean_xss_tags(trim($_GET[s_idx]));
if(!$s_idx || $s_idx < 1) 
{
	$errorstr = "키값오류입니다.";
	$errorurl = "";
	$returnVal = returnErrorArr($errorstr,$errorurl); echo $returnVal; exit; 
}

$ship = sql_fetch(" select * from m_ship where s_idx = '{$s_idx}' ");
if(!$ship[s_idx]) 
{
	$errorstr = "존재하지 않는 어선입니다.";
	$errorurl = "";
	$returnVal = returnErrorArr($errorstr,$errorurl); echo $returnVal; exit; 
}
if($ship[s_expose] != "y") 
{
	$errorstr = "현재 해당 어선은 선택하실 수 없습니다.";
	$errorurl = "";
	$returnVal = returnErrorArr($errorstr,$errorurl); echo $returnVal; exit; 
}
$ship_name = get_text($ship[s_name]);
$ship_addr = get_text($ship[s_addr]);
//===================== 어선정보가져옴 ========================

//===================== 달력정보가져옴 ========================
$sc_ymd = trim($_REQUEST[sc_ymd]);
$sc_ymd = (int)preg_replace('/[^0-9]/', '', $sc_ymd);

//날짜자리수체크
if (strlen($sc_ymd) == 6) // 달력에서 prev, next 버튼으로 넘어온 경우, 즉 지난달, 다음달 선택시 (201807)
{
	$is_specific = "0";
	// 개별날짜 추출
	$sc_y = substr($sc_ymd,0,4);
	$sc_m = substr($sc_ymd,4,2);
	$sc_d = "01";
	$sc_ymd = $sc_y.$sc_m.$sc_d;
}
else if (strlen($sc_ymd) == 8) // 달력에서 특정날짜를 선택해서 넘어온 경우 (20180715)
{
	$is_specific = "1";
	// 개별날짜 추출
	$sc_y = substr($sc_ymd,0,4);
	$sc_m = substr($sc_ymd,4,2);
	$sc_d = substr($sc_ymd,6,2);
}
else
{
	$errorstr = "예약일자를 선택해 주세요.";
	$errorurl = "";
	$returnVal = returnErrorArr($errorstr,$errorurl); echo $returnVal; exit; 
}

if(!$sc_ymd || $sc_ymd < 20000101 || $sc_ymd > 20501231)
{
	$errorstr = "예약일자를 선택해 주세요.";
	$errorurl = "";
	$returnVal = returnErrorArr($errorstr,$errorurl); echo $returnVal; exit; 
}


$sc = sql_fetch(" select * from m_schedule where s_idx='{$s_idx}' and sc_ymd='{$sc_ymd}' ");
// 해당일 설정상태
$sc_status = $sc[sc_status];

// 출조제목
$sc_theme = " - ".get_text($sc[sc_theme]);
if($sc[sc_idx]) $sc_theme = " - ".get_text($sc[sc_theme]);
else $sc_theme = get_text($sc[sc_theme]);

// 출조지점
$sc_point = get_text($sc[sc_point]);

// 출조인원
$sc_max = $sc[sc_max];

// 출조가격
$sc_price = $sc[sc_price];

// 출조공지
$sc_desc = get_text($sc[sc_desc],1);

// 예약접수인원
$b0sql = " select bk_mb_name, bk_member_cnt from m_bookdata where bk_ymd='{$sc_ymd}' and bk_status='0' and s_idx='{$s_idx}' ";
$b0result=sql_query($b0sql);
$b0cnt = sql_num_rows($b0result);
if($b0cnt > 0) {
	$b0_array="";
	for($i=0;$row0=sql_fetch_array($b0result);$i++) {
		if($b0_array=="") $b0_array=$row0[bk_mb_name]."(".$row0[bk_member_cnt]."명)";
		else $b0_array .= ", ".$row0[bk_mb_name]."(".$row0[bk_member_cnt]."명)";
	}
} else {
	$b0_array="없음.";
}

// 예약완료인원
$b1sql = " select bk_mb_name, bk_member_cnt from m_bookdata where bk_ymd='{$sc_ymd}' and bk_status='1' and s_idx='{$s_idx}' ";
$b1result=sql_query($b1sql);
$b1cnt = sql_num_rows($b1result);
if($b1cnt > 0) {
	$b1_array="";
	for($i=0;$row1=sql_fetch_array($b1result);$i++) {
		if($b1_array=="") $b1_array=$row1[bk_mb_name]."(".$row1[bk_member_cnt]."명)";
		else $b1_array .= ", ".$row1[bk_mb_name]."(".$row1[bk_member_cnt]."명)";
	}
} else {
	$b1_array="없음.";
}

// 예약접수/완료 html
$sc_bk_members='<div id="sc_members">';
$sc_bk_members.='<ul class="list-unstyled lists-v1">';
$sc_bk_members.='<li><i class="fa fa-angle-right"></i>예약대기(입금대기) : '.$b0_array.'</li>';
$sc_bk_members.='<li><i class="fa fa-angle-right"></i>예약완료(입금완료) : '.$b1_array.'</li>';
$sc_bk_members.='</ul>';
$sc_bk_members.='</div>';

// 예약가능인원
$available = $sc[sc_max] - $sc[sc_booked];
$available_str = $available."명";
if(!$sc[sc_idx]) {
	$available = 0;
	$available_str = '<span style="color:red;">예약불가</span>';
} else {
	if($sc[sc_status] != "0") {
		$available = 0;
		$available_str = '<span style="color:red;">예약마감</span>';
	}
	if($available == "0") {
		$available = 0;

        if($comfig['overbooking'] == "1") {
            $available_str = '<span style="color:blue;">대기접수가능</span>';
        } else {
            $available_str = '<span style="color:red;">예약마감</span>';
        }
    }
}
$sc_theme_total = "<span style='color:blue;font-weight:bolder;'>".$ship_name."</span> ";
$sc_theme_total .= "<span style='color:red;font-weight:bolder;'>".$sc_m."월 ".$sc_d."일 </span> ";
$sc_theme_total .= "<span style='color:blue;font-weight:bolder;'>".$sc_theme."</span>";

$selBox = "<option value=''>인원선택</option>";
for($i=0;$i<$sc_max;$i++) {

    $addOptStr = "";
    if($comfig['overbooking'] == "1") {
        if($i>=$available) {
            $addOptStr = "(대기접수)";
        }
    }    
	$j=$i+1;
	$selBox .= "<option value='".$j."'>".$j."명".$addOptStr."</option>";
}

$sc_cont = '';
// 해당일 기상정보
$fcam = sql_fetch(" select * from m_fcst where ymd='{$sc_ymd}' and dayb='오전' ");
if($fcam[ymd]) {
	$wicon = "<img src='".$fcam[wicon]."' style='height:15px;'>";
	$sc_cont .= '<div class="fcstWrap">';
	$sc_cont .= '<div class="fcstdb">오전</div>';
	$sc_cont .= '<div class="fcstdata">';
	$sc_cont .= '<div class="fcst">';
	$sc_cont .= '<span class="txt-bold">날씨</span><span class="txt-fcst"> : '.$fcam[sky].$wicon.'</span>';
	$sc_cont .= '<span class="txt-bold">파고</span><span class="txt-fcst"> : '.$fcam[wv].'</span>';
	$sc_cont .= '</div>';
	if($fcam[wd] && $fcam[ws]) {
		$sc_cont .= '<div class="fcst">';
		$sc_cont .= '<span class="txt-bold">풍향</span><span class="txt-fcst"> : '.$fcam[wd].'</span>';
		$sc_cont .= '<span class="txt-bold">풍속</span><span class="txt-fcst"> : '.$fcam[ws].'</span>';
		$sc_cont .= '</div>';
		$sc_cont .= '</div>';
	}
	$sc_cont .= '</div>';
	$sc_cont .= '</div>';
}

$fcpm = sql_fetch(" select * from m_fcst where ymd='{$sc_ymd}' and dayb='오후' ");
if($fcpm[ymd]) {
	$wicon = "<img src='".$fcpm[wicon]."' style='height:15px;'>";
	$sc_cont .= '<div class="fcstWrap2">';
	$sc_cont .= '<div class="fcstdb">오후</div>';
	$sc_cont .= '<div class="fcstdata">';
	$sc_cont .= '<div class="fcst">';
	$sc_cont .= '<span class="txt-bold">날씨</span><span class="txt-fcst"> : '.$fcpm[sky].$wicon.'</span>';
	$sc_cont .= '<span class="txt-bold">파고</span><span class="txt-fcst"> : '.$fcpm[wv].'</span>';
	$sc_cont .= '</div>';
	if($fcpm[wd] && $fcpm[ws]) {
		$sc_cont .= '<div class="fcst">';
		$sc_cont .= '<span class="txt-bold">풍향</span><span class="txt-fcst"> : '.$fcpm[wd].'</span>';
		$sc_cont .= '<span class="txt-bold">풍속</span><span class="txt-fcst"> : '.$fcpm[ws].'</span>';
		$sc_cont .= '</div>';
		$sc_cont .= '</div>';
	}
	$sc_cont .= '</div>';
	$sc_cont .= '</div>';
}

if(!$fcpm[ymd] && !$fcam[ymd]) {
	$fcall = sql_fetch(" select * from m_fcst where ymd='{$sc_ymd}' and dayb='종일' ");
	if($fcall[ymd]) {
		$wicon = "<img src='".$fcall[wicon]."' style='height:15px;'>";
		$sc_cont .= '<div class="fcstWrap2">';
		$sc_cont .= '<div class="fcstdb">종일</div>';
		$sc_cont .= '<div class="fcstdata">';
		$sc_cont .= '<div class="fcst">';
		$sc_cont .= '<span class="txt-bold">날씨</span><span class="txt-fcst"> : '.$fcall[sky].$wicon.'</span>';
		$sc_cont .= '<span class="txt-bold">파고</span><span class="txt-fcst"> : '.$fcall[wv].'</span>';
		$sc_cont .= '</div>';
		if($fcall[wd] && $fcall[ws]) {
			$sc_cont .= '<div class="fcst">';
			$sc_cont .= '<span class="txt-bold">풍향</span><span class="txt-fcst"> : '.$fcall[wd].'</span>';
			$sc_cont .= '<span class="txt-bold">풍속</span><span class="txt-fcst"> : '.$fcall[ws].'</span>';
			$sc_cont .= '</div>';
			$sc_cont .= '</div>';
		}
		$sc_cont .= '</div>';
		$sc_cont .= '</div>';
	}
}

// 해당일 상세출조스케쥴
$sc_cont .= '<ul class="list-unstyled lists-v1">';
$sc_cont .= '<li><i class="fa fa-angle-right"></i>남은자리 : '.$available_str.'</li>';
$sc_cont .= '<li><i class="fa fa-angle-right"></i>출조지점 : '.$sc_point.'</li>';
$sc_cont .= '<li><i class="fa fa-angle-right"></i>출조비용 : 1인당 '.number_format($sc_price).'원</li>';
$sc_cont .= '<li><i class="fa fa-angle-right"></i>배타는곳 : '.$ship_addr.'</li>';
$sc_cont .= '<li><i class="fa fa-angle-right"></i>안내사항 : '.$sc_desc.'</li>';
$sc_cont .= '</ul>';

//===================== 달력정보가져옴 ========================

// 전체 등록어선 예약가능인원수 추출
$sql = " select * from m_ship where s_expose = 'y' ";
$result = sql_query($sql);
$_ship_arr = "";
$_ship_selbox = "";

for($i=0; $row=sql_fetch_array($result);$i++) {
	 $is_selected = "";
	 $is_selected2 = "";
	$_ship_name = get_text($row[s_name]);
	
	$_ship_scsql = sql_fetch(" select * from m_schedule where s_idx = '{$row[s_idx]}' and sc_ymd = '{$sc_ymd}' ");
	$_available = $_ship_scsql[sc_max] - $_ship_scsql[sc_booked];
	
    if(!$_ship_scsql['sc_idx']) {
        $_available = "0";
    } else {
        if($_ship_scsql[sc_status] != "0") {
            $_available = "마감";
        } else {
            if($_available== "0") {
                $_available = "마감";
                if($comfig['overbooking'] == "1") {
                    $_available = '대기접수';
                } else {
                    $_available = '마감';
                }
            }
        }
    }

	if($row[s_idx]==$s_idx)  { $is_selected = " on";  $is_selected2 = " selected='selected' ";}
	else {$is_selected = "";  $is_selected2 = "";}

	if($is_specific == "0")	{	
		$_ship_arr .= "<li class='ship-li'><span id='shipLi_".$row[s_idx]."' class='ship-name".$is_selected."' data-sidx='".$row[s_idx]."'>".$_ship_name."</span></li>";
	} else if($is_specific == "1") {	
		$_ship_arr .= "<li class='ship-li'><span id='shipLi_".$row[s_idx]."' class='ship-name".$is_selected."' data-sidx='".$row[s_idx]."'>".$_ship_name."(".$_available.")</span></li>";
	}

	$_ship_selbox .= "<option value='".$row[s_idx]."'".$is_selected2.">".$_ship_name."</option>";
}

$svcdivstr = '';
$k=0; 
while($k < count($_menu_arr)) { 
	$m_subj = $_menu_arr[$k][0];
	$m_key = $_menu_arr[$k][1];
	
	$chkstr = "";
	if(in_array($m_key, array_map("trim", explode('|', $ship[s_service])))) $chkstr = "checked='checked' ";
	$k++; 
	
	$svcdivstr .= '<div class="col-xxs-6 col-xs-4 col-sm-4">';
	$svcdivstr .= '<input type="checkbox" id="svc_'.$m_key.'" '.$chkstr.'  onclick="return false;" />';
	$svcdivstr .= '<label for="svc_'.$m_key.'" style="padding-left:4px;">'.$m_subj.'</label>';
	$svcdivstr .= '</div>';
}

ob_start();
include './ajax_get_schedule_skin.php'; 
$content = ob_get_contents();
ob_end_clean();
$shipImgs = $content;

$returnVal = json_encode(
	array(
		"rslt"=>"ok", 
		"s_idx"=>$s_idx,
		"sc_ymd"=>$sc_ymd,
		"sc_status"=>$sc_status,
		"sc_bk_members"=>$sc_bk_members,
		"sc_theme_total"=>$sc_theme_total,
		"sc_theme"=>$sc_theme,
		"sc_cont"=>$sc_cont,
		"sc_price"=>$sc_price,
		"sc_desc"=>$sc_desc,
		"selbox"=>$selBox,
		"shipselbox"=>$_ship_selbox,
		"ship_arr"=>$_ship_arr,
		"shipImgs"=>$shipImgs,
		"svcdivstr"=>$svcdivstr,
		"available_str"=>$available_str,
		"spec" => $is_specific
	)
);

echo $returnVal;
?>
