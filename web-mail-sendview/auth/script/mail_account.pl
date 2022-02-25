#!/usr/bin/perl

# 文字コード Charset EUC-JP

use strict;


sub GetMailAccount {
	# 引数をローカル変数に格納
	my $strNickname = $_[0];

	# データファイル
	my $strDataFileName = '/home/tmg1136-inue2/auth/data/mailaccount.csv';
	# アカウントデータ (server, user, password)
	my @aryAccount = ('', '', '');
	# CSV解析用一時データ
	my @aryData = [];
	my $nItemCount = 0;
	# ファイルハンドル
	my $hFile;

	if(!open($hFile, $strDataFileName))
	{	# ファイルが開けない場合
		return(@aryAccount);
	}

	while(my $strTmp = <$hFile>)
	{
		if($strTmp =~ /^#/)
		{	# コメント行
			next;
		}
		@aryData = split(/,/, $strTmp);
		$nItemCount = @aryData;
		if($nItemCount < 4)
		{	# メールアカウントのデータカラムは、4カラム
			next;
		}

		if($aryData[0] eq $strNickname)
		{
			$aryAccount[0] = $aryData[1];
			$aryAccount[1] = $aryData[2];
			$aryAccount[2] = $aryData[3];
			last;
		}
	}

	return(@aryAccount);
}


;1;
