function handleActiveTab() {

    var clickedId = event.currentTarget.id;
    var isActive = document.getElementById(clickedId).classList.contains('active');

    if(event.currentTarget.id === 'tab1' && isActive === false) {

        document.getElementById('search_user').value = '';
        document.getElementById('search_linked').value = '';

        document.getElementById('tab2').classList.remove('active');

        document.getElementById('tab1').classList.remove('inactive');
        document.getElementById('tab1').classList.add('active');

        document.querySelector('[data-name = "tab1"]').classList.remove('d-none');
        document.querySelector('[data-name = "tab2"]').classList.add('d-none');

    } else if ( event.currentTarget.id === 'tab2' && isActive === false ) {

        document.getElementById('search_user').value = '';
        document.getElementById('search_linked').value = '';

        document.getElementById('tab1').classList.remove('active');

        document.getElementById('tab2').classList.remove('inactive');
        document.getElementById('tab2').classList.add('active');

        document.querySelector('[data-name = "tab2"]').classList.remove('d-none');
        document.querySelector('[data-name = "tab1"]').classList.add('d-none');

    } else {
        return;
    }
}

function searchList() {

    var input = document.getElementById(event.currentTarget.id);
    var filter = input.value.toUpperCase();
    var ul;
    if(event.currentTarget.id === 'search_user') {
        ul = document.getElementById('list_user');
    } else if (event.currentTarget.id === 'search_linked') {
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

function handlePopover() {

    var id = event.currentTarget.id;
    var elem = document.querySelectorAll('[data-name = "'+id+'"');


    elem[1].classList.toggle('open');
    elem[0].classList.toggle('fa-angle-down');
    elem[0].classList.toggle('fa-angle-up');

}