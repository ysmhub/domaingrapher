<?php

function create_graph_link($from, $to, $style) {
    $out = "\t" . '"' . $from . '"' .
            " -> " . 
            '"' . $to . '"' .
            PHP_EOL;
    return $out;
}

function create_node_color($node, $shape, $color) {
    $out = "\t" . '"' . $node . '"' .
            ' [' . "shape=$shape, style=filled, fillcolor=" . $color . ']' .
            PHP_EOL;
    return $out;
}

function create_node_common($node, $shape, $color) {
    $out = "\t" . '"' . $node . '"' .
            ' [' . "shape=$shape, style=filled, fillcolor=" . $color . ']' .
            PHP_EOL;
    return $out;
}

$domains = array();
array_push($domains, "liga.net");
array_push($domains, "unian.ua");
array_push($domains, "ukr.net");
array_push($domains, "olx.ua");
array_push($domains, "pravda.com.ua");
array_push($domains, "epravda.com.ua");

$reports = array();
foreach ($domains as $domain) {
    $element["domain"] = $domain;
    $result = dns_get_record($domain, DNS_A);
    $element["DNS_A"] = $result;
    $result = dns_get_record($domain, DNS_NS);
    $element["DNS_NS"] = $result;
    $result = dns_get_record($domain, DNS_MX);
    $element["DNS_MX"] = $result;

    $mxs = array();
    foreach ($result as $mx) {
        $result = dns_get_record($mx["target"], DNS_A);
        $mxs[$mx["target"]] = $result;
    }
    $element["DNS_MX_A"] = $mxs;

    array_push($reports, $element);
};

$dns_a = array();
foreach ($reports as $r) {
    foreach ($r["DNS_A"] as $x) {
        $dns_a[] = $x["ip"];
    }    
}
$dns_a_count = array_count_values($dns_a);
$dns_a_common = array();
foreach ($dns_a_count as $key => $value) {
    if ($value > 1) $dns_a_common[] = $key;
}

$dns_ns = array();
foreach ($reports as $r) {
    foreach ($r["DNS_NS"] as $x) {
        $dns_ns[] = $x["target"];
    }    
}
$dns_ns_count = array_count_values($dns_ns);
$dns_ns_common = array();
foreach ($dns_ns_count as $key => $value) {
    if ($value > 1) $dns_ns_common[] = $key;
}

$dns_mx = array();
foreach ($reports as $r) {
    foreach ($r["DNS_MX"] as $x) {
        $dns_mx[] = $x["target"];
    }    
}
$dns_mx_count = array_count_values($dns_mx);
$dns_mx_common = array();
foreach ($dns_mx_count as $key => $value) {
    if ($value > 1) $dns_mx_common[] = $key;
}

$dns_mxa = array();
foreach ($reports as $r) {
    foreach ($r["DNS_MX_A"] as $x) {
        $dns_mxa[] = $x[0]["ip"];
    }    
}
$dns_mxa_count = array_count_values($dns_mxa);
$dns_mxa_common = array();
foreach ($dns_mxa_count as $key => $value) {
    if ($value > 1) $dns_mxa_common[] = $key;
}

$str = "digraph G {" . PHP_EOL;
foreach ($domains as $d) {
    $str .= create_node_color($d, 'box', 'gold');
}
foreach ($reports as $r) {
    foreach ($r["DNS_A"] as $x) {
        if (in_array($x["ip"], $dns_a_common)) {
            $str .= create_node_common($x["ip"], 'box', 'red');
        } else {
            $str .= create_node_color($x["ip"], 'box', 'green');
        }
        $str .= create_graph_link($r["domain"], $x["ip"], NULL);
    }

    foreach ($r["DNS_NS"] as $x) {
        if (in_array($x["target"], $dns_ns_common)) {
            $str .= create_node_common($x["target"], 'box', 'red');
        } else {
            $str .= create_node_color($x["target"], 'box', 'cyan');
        }
        $str .= create_graph_link($r["domain"], $x["target"], NULL);
    }
    foreach ($r["DNS_MX"] as $x) {
        if (in_array($x["target"], $dns_mx_common)) {
            $str .= create_node_common($x["target"], 'box', 'red');
        } else {
            $str .= create_node_color($x["target"], 'box', 'plum1');
        }
        $str .= create_graph_link($r["domain"], $x["target"], NULL);
    }
    foreach ($r["DNS_MX_A"] as $x) {
        if (in_array($x[0]["ip"], $dns_mxa_common)) {
            $str .= create_node_common($x[0]["ip"], 'box', 'red');
        } else {
            $str .= create_node_color($x[0]["ip"], 'box', 'plum');
        }
        $str .= create_graph_link($r["domain"], $x[0]["ip"], NULL);
    }
}
$str .= "}" . PHP_EOL;

$graphname = 'graph_'.date('YmdHis');
$dotfilename = $graphname.'.dot';
file_put_contents($dotfilename, $str);

$pngfilename = $graphname.'.png';
exec("fdp -Tpng $dotfilename -o $pngfilename");
echo ("OK.".PHP_EOL);

?>