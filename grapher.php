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

$domains = array();
array_push($domains, "liga.net");
array_push($domains, "unian.ua");
array_push($domains, "ukr.net");
array_push($domains, "olx.ua");

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

$str = "digraph G {" . PHP_EOL;
foreach ($domains as $d) {
    $str .= create_node_color($d, 'box', 'gold');
}
foreach ($reports as $r) {
    foreach ($r["DNS_A"] as $x) {
        $str .= create_node_color($x["ip"], 'box', 'green');
        $str .= create_graph_link($r["domain"], $x["ip"], NULL);
    }

    foreach ($r["DNS_NS"] as $x) {
        $str .= create_node_color($x["target"], 'box', 'cyan');
        $str .= create_graph_link($r["domain"], $x["target"], NULL);
    }
    foreach ($r["DNS_MX"] as $x) {
        $str .= create_node_color($x["target"], 'box', 'plum1');
        $str .= create_graph_link($r["domain"], $x["target"], NULL);
    }
    foreach ($r["DNS_MX_A"] as $x) {
        $str .= create_node_color($x[0]["ip"], 'box', 'plum');
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