//generate password with 10 char length

function choice(arg) {
        return Math.floor(Math.random()*arg.length);
}

function randstr(arg) {
	var str = '';
	var seed = choice(arg);
	str = arg[seed];
	return str;
}

function initialize() {
        var count=new Date().getSeconds();
        for (c=0; c<count; c++)
        	Math.random();
}

function mkpass() {
        initialize();

        var pass_len=10;

        var cons_lo = ['b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z'];
        var cons_up = ['B','C','D','F','G','H','J','K','L','M','N','P','Q','R','S','T','V','W','X','Y','Z'];
        var hard_cons_lo = ['b','c','d','f','g','h','k','m','p','s','t','v','z'];
        var hard_cons_up = ['B','C','D','F','G','H','K','M','P','S','T','V','Z'];
		var link_cons_lo = ['h','l','r'];
		var link_cons_up = ['H','L','R'];
		var vowels_lo = ['a','e','i','o','u'];
		var vowels_up = ['A','E','I','U']; //O (letter o) and 0 (number zero) getconfused
        var digits = ['1','2','3','4','5','6','7','8','9'];

        //change at will how many times digits appears in names array. Order doesn't matter
        
        var names = [cons_lo, cons_up, digits, hard_cons_lo, hard_cons_up, digits, link_cons_lo, link_cons_up, digits, vowels_lo, vowels_up, digits];

		var newpass= '';
        for(i=0; i<pass_len; i++)
        	newpass = newpass + randstr(names[choice(names)]);

		return newpass;
}