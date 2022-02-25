#!/usr/bin/perl

# ʸ�������� Charset EUC-JP

use strict;


sub GetMailAccount {
	# ������������ѿ��˳�Ǽ
	my $strNickname = $_[0];

	# �ǡ����ե�����
	my $strDataFileName = '/home/tmg1136-inue2/auth/data/mailaccount.csv';
	# ��������ȥǡ��� (server, user, password)
	my @aryAccount = ('', '', '');
	# CSV�����Ѱ���ǡ���
	my @aryData = [];
	my $nItemCount = 0;
	# �ե�����ϥ�ɥ�
	my $hFile;

	if(!open($hFile, $strDataFileName))
	{	# �ե����뤬�����ʤ����
		return(@aryAccount);
	}

	while(my $strTmp = <$hFile>)
	{
		if($strTmp =~ /^#/)
		{	# �����ȹ�
			next;
		}
		@aryData = split(/,/, $strTmp);
		$nItemCount = @aryData;
		if($nItemCount < 4)
		{	# �᡼�륢������ȤΥǡ��������ϡ�4�����
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
