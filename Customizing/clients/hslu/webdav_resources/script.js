(function() {
	document.getElementById(document.getElementsByClassName('selected')[0].firstChild.getAttribute('href')).className+=' selected';

	tL = document.getElementById('menu').children;
	for (i=0,l=tL.length;i<l;i++){
		tL[i].firstChild.onclick =	function(){
			t = document.getElementById(this.getAttribute('href'));
			s = t.parentNode.children;
			for (j=0, n=s.length;j<n;j++){
				s[j].className='tab';
			}

			t.className+=' selected';
			return false;
		};
	}
})();