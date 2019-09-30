<?php
/**
 * Alipay.com Inc.
 * Copyright (c) 2004-2014 All Rights Reserved.
 */



include('../AopClient.php');
include '../request/AlipaySystemOauthTokenRequest.php';
header("Content-type: text/html; charset=gbk");

/**
 *
 * @author wangYuanWai
 * @version $Id: Test.hp, v 0.1 Aug 6, 2014 4:20:17 PM yikai.hu Exp $
 */
$aop = new AopClient ();
$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
$aop->appId = 'your APPid';
$aop->rsaPrivateKey = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDPc8dQIKEBJamZ87fwTCDMlm98HmByFLWQCedoPF1SCb/DtMsG83VuBoueFSkxgozlveRTQO9elEc/GFVJt8flpdSOwbqOPDZdFs2Mxsz28Clx5J9i3xYjIwb29QTE39txD4tnygNubsT9JoCtUK82OtN716asJjLWXPbsWDh/939itCjC+/e69hG2TuLNZjQWm9Ix7F6wSnpfJeAvkBh/bYvvm/pAHQDQa/cIL/lelSO0Axl+a4lRbfmaYQgxKIh9hRTnY1l9MwZztTDeiijsrOEqsIFcrUK1V6OiclZIrCSmWUGw1ENjKGLGOkAHAp7g7jRznIwkoH2bqI1s9MUhAgMBAAECggEAI28JKjyIg6OlNnCUiGzYZVmErVSa8KFs6a9nYTx+TBbyNEFbNFK9Sg/AJT5EDPrZihxHNZkvyZ3WFYbYeoDzmdwbVE7uyICsYHYWoecK91rKGXQNbVvhchr3g5MSP3ZlCwi2rQGqWGB/MSY0cGYJp1+g49RP2bOkl0zFuFTRQbn7eC8DuibIMRtmmYpEODpf6ti5WsCAmpNO6kceWIEHPey+5fQ8C7ySUFhtWWjnEt2c6YdqtAnmmvs5HFuRpX8pJqFQhPHT3vj96gPz5MtaF7t1Y9qep9PBA3PKIw6Scr2YGQkESOuLQbcDSYCgd+yHvLezUnlbsJTHDg+gGYB96QKBgQD7ZLx/IakcmZZa9M+sWL9/58PZdlE1xPIJxjXVSL5AyTLGpju1CM4Iba2GdQLrzfB31Q2RTs/8JvvU1SkqQuvTolZqr/Ha/AdxFrjOkv5gH9YtG0xr04i6L94rhriAaJNMARemZYYi9L6uIy3WSeuhyWGbuA29akgwwZ2EICo/lwKBgQDTQOr+uTAKePkVL1d4oN3z9e4PELqwSA9cY+z2vU2eGtrt10cRfHOPZOwjyIBsenG5M1bzRd5cbw7XtcnpKHGz8jlXbGaiqmgqxNDjccFS/3tWW2iZwZ7pOzjZgzdyCxcuNVqMwCv0rR1YO5bdcfbAI67O7dreDUI3ePhHnbw4BwKBgF71LypKWame7idPP76XC0bSEa1rvsMzsEU25JC62Hp6RT65/eWk/MY8P4aBXmgZsxJgnK5debyCMS+0kMcQ3iljsYa9DPstpdX2wjntVj6S6ADDxfsYvRWvDRmd2sVOOw1DgF5vDNrZXclDKoY+B85l1gPO4wnAQqKAD/Kff7XjAoGAX5gIvqteKd8EF6oMkvF4fmTTDM4tLIfvK4esOxr+fIT6fJXl+uut/z1T/f1E39GolH2+4ubC8GDw/nusmm1Kxnrdp8nNx94EtRXK0KExMjWZwkIq2yjal//MgeD2vgx2uNo712U+fsG3fa25Xwuq9Ncwy0Kmv8SQKjn7X6zo9xECgYATzNCuTydKk9EM7psZIcN3DcVw+b39m9CAryzZsOi5BEHnqpcZO6VdnJR5mS3IlPwrjKYj5aaTwDhEjYivQ/3A0cY0VHaF2mHBi30PEnV2+wKMx6jANCJg7slEt4ehZgtzRGAwrQMrGhuf6haqb0f2816m3mejkjy8xhjCFi/STQ==';
$aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4f7ekWB1rNarD4/xcox869lbEacfkze3yCiUotaqzis8K+ftr31FG6oshbVqJcUAfGvCHBxo0Djx71RPBkbHHZhC/W0RbVaY1miUhFAO0eLfLSEM/bRX3JXu/hKJLjb8HSouqvNMtOTTmHxybtq/+3IeUCuW02gG3RqXtTz8OocF31krKqhyJqWS1T2Tc/8AfZHW9YCfxG2XKYTmP9eLSH9br2K18TX8ipvCk5KXZRZdgP705cLU83KOZlUgrccBypPq9QsKXwHquRhItHmuJuU/VpVqQJ6SVx7mFNhtN4kvumUzKGM5+fA2344a4mfmuhTML2rA2VWx7meWr27PkQIDAQAB';
$aop->apiVersion = '1.0';
$aop->signType = 'RSA2';
$aop->postCharset='UTF-8';
$aop->format='json';
$request = new AlipaySystemOauthTokenRequest ();
$request->setGrantType("authorization_code");
$request->setCode("9c24afdca626490aaa7bdd479cc9ZX62");
//$request->setRefreshToken("");
$result = $aop->execute($request); 
var_dump($result);
$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
$resultCode = $result->$responseNode->code;
if(!empty($resultCode)&&$resultCode == 10000){
echo "成功";
} else {
echo "失败";
}