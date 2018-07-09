function handleActiveFirstTab() {
    var isActive = document.getElementById('tab1').classList.contains('active');
    if(isActive === false) {
        document.getElementById('search_user').value = '';
        document.getElementById('search_linked').value = '';

        document.getElementById('tab2').classList.remove('active');

        document.getElementById('tab1').classList.remove('inactive');
        document.getElementById('tab1').classList.add('active');

        document.querySelector('[data-name = "tab1"]').classList.remove('d-none');
        document.querySelector('[data-name = "tab2"]').classList.add('d-none');
    }
}

function handleActiveSecondTab() {
    var isActive = document.getElementById('tab2').classList.contains('active');
    if(isActive === false) {
        document.getElementById('search_user').value = '';
        document.getElementById('search_linked').value = '';

        document.getElementById('tab1').classList.remove('active');

        document.getElementById('tab2').classList.remove('inactive');
        document.getElementById('tab2').classList.add('active');

        document.querySelector('[data-name = "tab2"]').classList.remove('d-none');
        document.querySelector('[data-name = "tab1"]').classList.add('d-none');
    }
}


function searchListUsers() {
    var id = 'search_user';
    var input = document.getElementById(id);
    var filter = input.value.toUpperCase();
    var ul;
    if(id === 'search_user') {
        ul = document.getElementById('list_user');
    } else if (id === 'search_linked') {
        ul = document.getElementById('list_linked');
    }
    var a = ul.getElementsByTagName('a');
    var li;

    for (var i=0; i<a.length; i++) {
        li = a[i].getElementsByTagName('li')[0];
        if(li.innerHTML.toUpperCase().indexOf(filter) > -1) {
            a[i].style.display = "";
        } else {
            a[i].style.display = "none";
        }
    }
}

function searchListLinked() {
    var id = 'search_linked';
    var input = document.getElementById(id);
    var filter = input.value.toUpperCase();
    var ul;
    if(id === 'search_user') {
        ul = document.getElementById('list_user');
    } else if (id === 'search_linked') {
        ul = document.getElementById('list_linked');
    }
    var a = ul.getElementsByTagName('a');
    var li;

    for (var i=0; i<a.length; i++) {
        li = a[i].getElementsByTagName('li')[0];
        if(li.innerHTML.toUpperCase().indexOf(filter) > -1) {
            a[i].style.display = "";
        } else {
            a[i].style.display = "none";
        }
    }
}

function searchProducts() {


    var input = document.getElementById('products');
    var filter = input.value.toUpperCase();
    console.log(input);
    var elems = document.querySelectorAll('[data-name = "product"]');
    var rows = document.querySelectorAll('[data-name = "row"]');

    for(var i=0; i<elems.length; i++) {
        if(elems[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }

}

function handlePopover(id) {

    if(id == undefined) {
        var id = event.currentTarget.id;
    }

    var elem = document.querySelectorAll('[data-name = "'+event.currentTarget.id+'"');

    elem[1].classList.toggle('open');
    elem[0].classList.toggle('fa-angle-down');
    elem[0].classList.toggle('fa-angle-up');

}

function toggleMenu(target) {
    target = event.currentTarget.dataset.target;
    document.getElementById(target).classList.toggle('show');
}

function toggleIndexMenu() {
    document.getElementById('navbarCollapse').classList.toggle('show');
}

function toggleAppMenu(target) {
    document.getElementById('appNav').classList.toggle('menu-display');
}