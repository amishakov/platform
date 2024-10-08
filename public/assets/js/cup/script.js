"use strict";

$(() => {
    // sidebar
    let $sidebar = $('.toggle-sidebar');
    $sidebar.on('click', (e) => {
        let $wrapper = $(".wrapper");

        switch ($wrapper.hasClass('sidebar_minimize')) {
            case false:
                $sidebar
                    .addClass('toggled')
                    .html('<i class="fas fa-ellipsis-v"></i>');

                localStorage.setItem('sidebar', '1');
                break;

            case true:
                $sidebar
                    .removeClass('toggled')
                    .html('<i class="fas fa-ellipsis-h"></i>');

                localStorage.setItem('sidebar', '0');
                break;
        }

        $wrapper.toggleClass('sidebar_minimize');
    });

    if(localStorage.getItem('sidebar') === '1') {
        $sidebar.click();
    }

    // toolbar
    let topbar_open = 0,
        $topbar = $('.topbar-toggler');
    $topbar.on('click', () => {
        if (topbar_open === 1) {
            $('html').removeClass('topbar_open');
            $topbar.removeClass('toggled');
            topbar_open = 0;
        } else {
            $('html').addClass('topbar_open');
            $topbar.addClass('toggled');
            topbar_open = 1;
        }
    });

    // sidenav
    let nav_open = 0,
        $nav_el = $('.sidenav-toggler');
    $nav_el.on('click', () => {
        if (nav_open === 1) {
            $('html').removeClass('nav_open');
            $nav_el.removeClass('toggled');
            nav_open = 0;
        } else {
            $('html').addClass('nav_open');
            $nav_el.addClass('toggled');
            nav_open = 1;
        }
    });

    // sidebar
    $('body').on('click', '.quick-sidebar-toggler, .close-quick-sidebar, .quick-sidebar-overlay', (e) => {
        $(e.currentTarget).toggleClass('toggled');
        $('html').toggleClass('quick_sidebar_open');

        let $el;
        if (($el = $('.quick-sidebar-overlay')) && $el.length) {
            $el.remove();
        } else {
            $('<div class="quick-sidebar-overlay"></div>').insertAfter('.quick-sidebar');
        }
    });

    // scrollbars
    $('.sidebar .scrollbar').scrollbar();
    $('.main-panel .content-scroll').scrollbar();
    $('.quick-scroll').scrollbar();
    $('.quick-actions-scroll').scrollbar();

    // navigation highlight
    let buf = 0, $active = null;
    $($('.sidebar a').get().reverse()).each((i, el) => {
        let $el = $(el),
            href = $el.attr('href');

        if (location.pathname.startsWith(href) && href.length > buf) {
            buf = href.length;
            $active = $el;
        }
    });

    $active
        .parent('li')
        .addClass('active')
        .parents('.nav-item, .submenu')
        .each((i, el) => $(el).find('[href^="#"]:first').click());

    $('[data-toggle="tooltip"]').tooltip()

    // select2 init
    let init_select2 = ($el) => {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap',
            width: $el.data('width') ? $el.data('width') : '100%',
            data: $el.data('data') ? $el.data('data') : null,
            ajax: $el.data('ajax') ? {url: $el.data('ajax'), dataType: 'json'} : null,
            placeholder: $el.data('placeholder') ? $el.data('placeholder') : null,
            minimumResultsForSearch: $el.data('search') !== undefined ? $el.data('search') : -1,
            allowClear: $el.data('allow-clear') ? $el.data('allow-clear') : false,
        });
    }
    $('[data-input] select').each((i, el) => init_select2($(el)));

    // main catalog stats
    {
        let canvas = document.querySelector('#orders_revenue');
        let data = window.orders_revenue ?? [];

        if (canvas && data.length) {
            let labels = data.map(item => item.date);
            let orderCounts = data.map(item => item.order_count);
            let sums = data.map(item => item.sum);
            let averageChecks = data.map(item => item.average_check);

            let statisticsChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'SUM',
                            data: sums,
                            type: 'line',
                            borderColor: '#177dff',
                            pointStyle: 'star',
                            pointBackgroundColor: 'rgba(23, 125, 255, 0.6)',
                            pointRadius: 4,
                            backgroundColor: 'rgba(23, 125, 255, 0.1)',
                            legendColor: 'rgba(23, 125, 255, 1)',
                            fill: true,
                            borderWidth: 2,
                            yAxisID: 'y-axis-1',
                            tension: 0.5,
                        },
                        {
                            label: 'AVG',
                            data: averageChecks,
                            type: 'line',
                            borderColor: '#fdaf4b',
                            pointStyle: 'rectRot',
                            pointBackgroundColor: 'rgba(253, 175, 75, 0.6)',
                            pointRadius: 3,
                            backgroundColor: 'rgba(253, 175, 75, 0.3)',
                            legendColor: 'rgba(253, 175, 75, 1)',
                            fill: true,
                            borderWidth: 2,
                            tension: 0.3,
                            yAxisID: 'y-axis-1',
                        },
                        {
                            label: 'COUNT',
                            data: orderCounts,
                            type: 'bar',
                            borderColor: 'rgba(104, 97, 206, 0.3)',
                            backgroundColor: 'rgba(104, 97, 206, 0.2)',
                            legendColor: 'rgba(104, 97, 206, 1)',
                            fill: true,
                            borderWidth: 2,
                            borderRadius: Number.MAX_VALUE,
                            yAxisID: 'y-axis-2',
                        },
                    ]
                },
                options: {
                    responsive: true,
                    hover: {
                        mode: 'index',
                        intersec: false
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                            },
                        },
                        tooltip: {
                            usePointStyle: true,
                        }
                    }
                }
            });

            // on resize
            window.addEventListener('resize', () => statisticsChart.resize());
        }
    }

    // publication preview
    $('form [data-click="preview"]').on('click', (e) => {
        e.preventDefault();

        let $form = $(e.currentTarget).parents('form'),
            preview = window.open('/cup/publication/preview', 'prv', 'height=400,width=750,left=0,top=0,resizable=1,scrollbars=1');

        $form.attr('action', '/cup/publication/preview');
        $form.attr('target', 'prv');
        $form.submit();

        preview.focus();

        setTimeout(() => {
            $form.attr('action', '');
            $form.attr('target', '_self');
        }, 500);
    });

    // icon copy (uuid) to clipboard
    $('i[data-copy]').on('click', (e) => {
        let $el = $(e.currentTarget),
            value = $el.attr('data-value');

        if (value) {
            navigator.clipboard.writeText(value).then(
                () => $el.toggleClass('fa-copy fa-check'),
                () => $el.toggleClass('fa-copy fa-times')
            );
        } else {
            $el.toggleClass('fa-copy fa-smile');
        }

        setTimeout(() => $el.removeClass('fa-check fa-times fa-smile').addClass('fa-copy'), 2500);
    });

    // modal window settings
    $.modal.defaults = {
        closeExisting: true,      // Close existing modals. Set this to false if you need to stack multiple modal instances.
        escapeClose: true,        // Allows the user to close the modal by pressing `ESC`
        clickClose: true,         // Allows the user to close the modal by clicking the overlay
        closeText: 'Close',       // Text content for the close <a> tag.
        closeClass: '',           // Add additional class(es) to the close <a> tag.
        showClose: false,         // Shows a (X) icon/link in the top-right corner
        modalClass: 'modal',      // CSS class added to the element being displayed in the modal.
        blockerClass: 'blocker',  // CSS class added to the overlay (blocker).
        spinnerHtml: '<div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div>',
        showSpinner: true,        // Enable/disable the default spinner during AJAX requests.
        fadeDuration: null,       // Number of milliseconds the fade transition takes (null means no transition)
        fadeDelay: 1.0            // Point during the overlay's fade-in that the modal begins to fade in (.5 = 50%, 1.5 = 150%, etc.)
    };

    // tabs save position
    {
        let key = 'nav-tabs',
            $navs = $('.nav.nav-pills');

        if ($navs.length) {
            let
                pathname = location.pathname,
                params = JSON.parse(sessionStorage.getItem(key) ?? '{}')
            ;

            $navs.each((i, el) => {
                params[pathname] = params[pathname] ?? {};

                $(el).find('.nav-item a.nav-link, a.nav-link').on('click', (el) => {
                    params[pathname][i] = $(el.currentTarget).attr('href');
                    sessionStorage.setItem(key, JSON.stringify(params));
                });
            });

            if (params[pathname]) {
                for (let i in params[pathname]) {
                    $('a[href="' + params[pathname][i] + '"]').click();
                }
            }
        }
    }

    // parameters guest user && user group
    {
        let $select = $('select[name="access[]"], [name="user[access][]"]'),
            $options = $select.find('option');

        $('[data-access-click]').on('click', (e) => {
            let $btn = $(e.currentTarget),
                type = $btn.attr('data-access-click');

            if (type === 'none') {
                $options.prop('selected', false);
            } else {
                $options.each((i, el) => {
                    let $buf = $(el);

                    if ($buf.val().startsWith(type)) {
                        $buf.prop('selected', !(+$buf.prop('selected')));
                    }
                });
            }

            $select.trigger('change.select2');
        });
    }

    // parameters add new entity key (API key)
    {
        $('[data-entity-click="add"]').on('click', (e) => {
            function key() {
                let d = new Date().getTime();

                return 'xxxx-xyyx-xxxx-yxxy'.replace(/[xy]/g, (c) => {
                    let r = (d + Math.random() * 16) % 16 | 0;

                    d = Math.floor(d / 16);

                    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                });
            }


            let $keys = $('[name="entity[keys]"]'),
                value = $keys.val();

            $keys.val((value ? value + "\n" : '') + key());
        })
    }

    // parameters add variables
    {
        let $hidden = $('[name="var[]"]').parents('[data-input]'),
            $value = $('[data-input="variable"]');

        $('[data-click="add_variable"]').on('click', () => {
            let $clone = $hidden.clone();

            if (!$value.val() || $('[name="var[' + $value.val() + ']"]').length) {
                $value.parents('.form-group').addClass('has-error');
                setTimeout(() => {
                    $value.parents('.form-group').removeClass('has-error');
                }, 2500);
            } else {
                $clone.find('.input-group-prepend span code').text($value.val());
                $clone.find('input')
                    .attr('type', 'text')
                    .attr('name', 'var[' + $value.val() + ']');

                $clone.insertBefore($hidden.parent());
                $value.val('');
            }
        });
    }

    // product attribute
    {
        let $that = $('[id="attributes"]'),
            $row = $that.find('[data-place="attribute"]'),
            templates = {
                $string: $that.find('[data-place="attribute"] [data-template="string"]').parents('[data-input]').detach(),
                $integer: $that.find('[data-place="attribute"] [data-template="integer"]').parents('[data-input]').detach(),
                $float: $that.find('[data-place="attribute"] [data-template="float"]').parents('[data-input]').detach(),
                $boolean: $that.find('[data-place="attribute"] [data-template="boolean"]').parents('[data-input]').detach(),
            },
            $select = $that.find('select[data-select="attributes"]');

        $that.find('button[data-click="add"]').on('click', () => {
            let $find = $that.find('[name="attributes[' + $select.val() + ']"]');

            if ($find.length === 0) {
                let type = attribute_types[$select.val()];
                let $buf = templates[`$${type}`].clone().removeAttr('data-template');

                $buf.find('label').text($select.find(':selected').text());
                $buf.find('input, select').attr('name', 'attributes[' + $select.val() + ']');

                if (type === 'boolean') {
                    $buf.find('.select2').remove();
                    init_select2($buf.find('select'));
                }

                $buf.appendTo($row);
            } else {
                $find.focus().addClass('border-danger');
                setTimeout(() => $find.removeClass('border-danger'), 2000);
            }
        });

        $that.find('button[data-click="delete"]').on('click', (e) => {
            $(e.currentTarget).parents('[data-input]').remove();
        });
    }

    // product relation
    {
        let $modal = $('[data-product-modal-product]');
        let $tab = $('#related');
        let $template = $tab.find('.list-group [style="display: none!important;"]').show().detach();

        // open modal
        $('[data-btn-product-modal-related]').on('click', () => {
            $modal.find('#products').html('');
            $modal.find('input[type="text"]').val('');
            $modal.find('input[type="number"]').val(1);

            $modal.modal();
        });

        // select product
        $modal.find('input[type="text"]').on('keyup', debounce((e) => {
            let $list = $modal.find('#products');

            if (e.target.value.trim().length) {
                $.get('/cup/api/v1/catalog/product', {title: e.target.value.trim()}, (res) => {
                    if (res.status === 200) {
                        $list.html('');

                        for (let item of res.data) {
                            $list.append(
                                $('<option>')
                                    .data('json', JSON.stringify(item))
                                    .attr('value', item.title)
                                    .text(item.price)
                            )
                        }
                    }
                });
            }
        }, 300));

        // confirm select
        $modal.find('[type="button"]').on('click', (e) => {
            let value = $modal.find('input[type="text"]').val();
            let $option = $modal.find(`#products option[value="${value}"]`)

            if ($option) {
                let data = JSON.parse($option.data('json')),
                    $isExist = $tab.find('[name="relations[' + data.uuid + ']"]');

                if ($isExist.length === 0) {
                    let $el = $template.clone();

                    $el.find('a').attr('href', `/cup/catalog/product/${data.uuid}/edit`).text(data.title);
                    $el.find('[name="relations[]"]').attr('name', 'relations[' + data.uuid + ']');

                    $el.appendTo($tab.find('ul.list-group'));
                } else {
                    $isExist.parents('li').addClass('has-error');
                    setTimeout(() => $isExist.parents('li').removeClass('has-error'), 2500);
                }
            }

            $.modal.close();
        })

        // remove product
        $tab.find('ul.list-group button').on('click', (e) => {
            e.preventDefault();

            $(e.currentTarget).parents('li').remove();
        });
    }

    // order form
    {
        // user select
        {
            let $modal = $('[data-order-modal-user]');

            // open modal
            $('[data-btn-order-modal-user]').on('click', () => {
                $modal.modal();
            });

            // select user
            $modal.find('input[list="users"]')
                .on('keyup', throttle((e) => {
                    let $list = $modal.find('#users');

                    if (e.target.value.trim().length) {
                        $.get('/cup/api/v1/user', {firstname: e.target.value.trim()}, (res) => {
                            if (res.status === 200) {
                                $list.html('');

                                for (let item of res.data) {
                                    $list.append(
                                        $('<option>')
                                            .attr('data-json', JSON.stringify(item))
                                            .attr('value', item.name.full)
                                            .text([item.phone, item.email].join(' ').trim())
                                    )
                                }
                            }
                        });
                    }
                }, 300))
                .on('change', (e) => {
                    let $option = $modal.find(`#users option[value="${e.target.value}"]`)

                    if ($option) {
                        let json = $option.attr('data-json'),
                            data = JSON.parse(json);

                        // user data
                        $modal.find('[name="user_uuid"]').val(data.uuid);
                        $modal.find('[name="firstname"]').val(data.firstname);
                        $modal.find('[name="lastname"]').val(data.lastname);
                        $modal.find('[name="phone"]').val(data.phone);
                        $modal.find('[name="email"]').val(data.email);
                        $modal.find('[name="country"]').val(data.country);
                        $modal.find('[name="city"]').val(data.city);
                        $modal.find('[name="postcode"]').val(data.postcode);
                        $modal.find('[name="address"]').val(data.address);
                        $modal.find('[name="company[title]"]').val(data.company.title);
                        $modal.find('[name="group_uuid"]').val(data.group.uuid).trigger('change.select2');

                    }
                });

            // confirm select
            $modal.find('button').on('click', (e) => {
                let template = $modal.find('select[data-address-format]').val();

                if (template) {
                    let data = {
                        'uuid': $modal.find('[name="user_uuid"]').val(),
                        'firstname': $modal.find('[name="firstname"]').val(),
                        'lastname': $modal.find('[name="lastname"]').val(),
                        'phone': $modal.find('[name="phone"]').val(),
                        'email': $modal.find('[name="email"]').val(),
                        'country': $modal.find('[name="country"]').val(),
                        'city': $modal.find('[name="city"]').val(),
                        'postcode': $modal.find('[name="postcode"]').val(),
                        'address': $modal.find('[name="address"]').val(),
                        'company.title': $modal.find('[name="company[title]"]').val(),
                        'group_uuid': $modal.find('[name="group_uuid"]').val(),
                    }

                    Object.keys(data).forEach((key) => {
                        template = template.replace(new RegExp(`{${key}}`, 'g'), data[key])
                    });

                    $('form [name="user_uuid"]').val(data.uuid);
                    $('form [name="phone"]').val(data.phone);
                    $('form [name="email"]').val(data.email);
                    $('form [name="delivery[address]"]').val(template);
                    $('form [name="delivery[client]"]').val(function (i, val) {
                        let buf = [data.lastname, data.firstname].filter((el) => !!el).join(' ');

                        return buf && val !== buf ? buf : val;
                    });

                    // save or update user data
                    if ((data.phone || data.email) && data.firstname && data.address) {
                        $.ajax({
                            method: data.uuid ? 'patch' : 'post',
                            url: '/cup/api/v1/user' + (data.uuid ? `?uuid=${data.uuid}` : ''),
                            data: unflatten(data),
                            complete: (result) => {
                                let res = result.responseJSON;

                                if (res.status === 201) {
                                    $('[name="user_uuid"]').val(res.data.uuid)
                                }
                            }
                        });
                    }
                }

                $.modal.close();
            })
        }

        // product select
        {
            let $modal = $('[data-order-modal-product]');
            let $table = $('[data-table="order"]');
            let $template = $table.find('[data-template] [data-product]').detach();

            // open modal
            $('[data-btn-order-modal-product]').on('click', () => {
                $modal.find('#products').html('');
                $modal.find('input[type="text"]').val('');
                $modal.modal();
            });

            // select product
            $modal.find('input[type="text"]').on('keyup', throttle((e) => {
                let $list = $modal.find('#products');

                if (e.target.value.trim().length) {
                    $.get('/cup/api/v1/catalog/product', {title: e.target.value.trim()}, (res) => {
                        if (res.status === 200) {
                            $list.html('');

                            for (let item of res.data) {
                                $list.append(
                                    $('<option>')
                                        .data('json', JSON.stringify(item))
                                        .attr('value', item.title)
                                        .text([item.vendorcode, item.barcode].filter((i) => !!i).join('; '))
                                )
                            }
                        }
                    });
                }
            }, 300));

            // confirm select
            $modal.find('button').on('click', () => {
                let value = $modal.find('input[type="text"]').val();
                let $option = $modal.find(`#products option[value="${value}"]`)

                if ($option) {
                    let json = $option.data('json'),
                        data = JSON.parse(json),
                        $find = $table.find(`[data-product="${data.uuid}"]`);

                    if ($find.length === 0) {
                        let $buf = $template.clone()
                            .attr('data-product', data.uuid)
                            .attr('data-price', data.calculated.price)
                            .attr('data-price-wholesale', data.calculated.price_wholesale)
                            .attr('data-discount', data.discount)
                        ;
                        $buf.find('span.select2')
                            .remove()
                        ;
                        $buf.find('a')
                            .attr('href', `/cup/catalog/product/${data.uuid}/edit`)
                            .text(data.title)
                        ;
                        $buf.find('[name="products[][price_type]"]')
                            .attr('name', 'products[' + data.uuid + '][price_type]')
                            .val('price')
                        ;
                        $buf.find('[name="products[][price]"]')
                            .attr('name', 'products[' + data.uuid + '][price]')
                            .val(data.calculated.price)
                        ;
                        $buf.find('[name="products[][discount]"]')
                            .attr('name', 'products[' + data.uuid + '][discount]')
                            .val(Math.abs(data.discount))
                        ;
                        $buf.find('[name="products[][count]"]')
                            .attr('name', 'products[' + data.uuid + '][count]')
                            .val(1)
                        ;
                        $buf.find('[data-subtotal]')
                            .text(data.calculated.price.toFixed(2))
                        ;

                        $buf.appendTo($table);

                        init_select2($buf.find('select'));
                    } else {
                        $find.addClass('border border-danger');
                        setTimeout(() => $find.removeClass('border border-danger'), 2000);
                    }

                    $.modal.close();
                }
            })

            // change price type
            $table.on('change', 'select', (e) => {
                let $input = $(e.currentTarget),
                    $row = $input.parents('[data-product]'),
                    $price = $row.find('input[name$="[price]"]'),
                    $discount = $row.find('input[name$="[discount]"]'),
                    isSelfPrice = $input.val() === 'price_self';

                $price.val(+($row.data($input.val()) ?? '0')).prop('readonly', !isSelfPrice);
                $discount.val(0);

                $price.trigger('change');
            });

            // update sum when change price, discount or quantity
            $table.on('change keyup', 'input[type="number"]', (e) => {
                let $input = $(e.currentTarget),
                    $row = $input.parents('[data-product]'),
                    price = $row.find('input[name$="[price]"]').val(),
                    discount = Math.abs($row.find('input[name$="[discount]"]').val()),
                    count = $row.find('input[name$="[count]"]').val();

                if (parseFloat(count) > 0) {
                    $row.find('[data-subtotal]').text((Math.max(0, (price - discount) * count)).toFixed(2));
                } else {
                    $row.remove();
                }

                $('[data-order-total]').text(
                    $('[data-subtotal]').toArray().reduce((sum, el) => sum + parseFloat(el.innerText), 0)
                )
            })
        }
    }

    // reference forms
    {
        // deliveries
        {
            $('[data-btn-reference-deliveries-add]').click((e) => {
                e.preventDefault();

                let $btn = $(e.currentTarget), $card = $btn.parents('.card'), $body = $card.find('.card-body');

                $body
                    .find('[data-delivery-row]')
                    .last()
                    .find('select')
                    .select2('destroy')
                    .removeAttr('data-select2-id')
                    .end()
                    .clone()
                    .find('select, input')
                    .val('')
                    .each((i, el) => {
                        let nameAttr = $(el).attr('name');

                        if (nameAttr) {
                            let newIndex = parseInt(nameAttr.match(/\[(\d+)\]/)[1]) + 1;
                            let newNameAttr = nameAttr.replace(/\[(\d+)\]/, '[' + newIndex + ']');
                            $(el).attr('name', newNameAttr);
                        }
                    })
                    .end()
                    .appendTo($body)
                ;

                $body.find('[data-input] select').each((i, el) => init_select2($(el)))
            });

            $(document).on('click', '[data-btn-reference-deliveries-remove]', (e) => {
                e.preventDefault();

                $(e.currentTarget).parents('[data-delivery-row]').remove();
            })
        }
    }

    window.print_element = function (selector) {
        let $html = $('html').clone(),
            $invoice = $html.find(selector).html(),
            $style = $('<style>* {background-color:#FFFFFF!important;}</style>');

        // replace
        $html.find('body').html($invoice).append($style);

        // open print window
        let print = window.open('', 'Print-Window');
            print.document.open();
            print.document.write($html.html());
            print.print();
        setTimeout(() => print.close(), 10);
    };
});
