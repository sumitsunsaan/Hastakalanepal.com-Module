{if !empty($products)}
<div class="block cvp-block" aria-label="{l s='Recommended products' mod='customerviewedproducts'}">
    <h3 class="cvp-title">{l s='Customers who viewed this item also viewed' mod='customerviewedproducts'}</h3>
    
    {if $carousel}
    <div class="owl-carousel cvp-carousel">
    {else}
    <div class="cvp-grid">
    {/if}
    
        {foreach from=$products item=product}
            <div class="cvp-item">
                <article class="product-miniature">
                    <div class="product-thumbnail">
                        <a href="{$product.url}" title="{$product.name}">
                            <img
                                src="{$product.cover.bySize.home_default.url}"
                                alt="{$product.name}"
                                loading="lazy"
                                width="{$product.cover.bySize.home_default.width}"
                                height="{$product.cover.bySize.home_default.height}"
                            >
                            {if $product.discount_percentage}
                                <span class="product-discount">{l s='-%s' sprintf=$product.discount_percentage mod='customerviewedproducts'}</span>
                            {/if}
                        </a>
                    </div>
                    <div class="product-description">
                        <h3 class="product-title"><a href="{$product.url}">{$product.name}</a></h3>
                        
                        <div class="product-price-and-shipping">
                            {if $product.has_discount}
                                <span class="regular-price">{$product.regular_price}</span>
                            {/if}
                            <span class="price">{$product.price}</span>
                        </div>
                        
                        {if $product.quantity > 0}
                            <div class="product-availability">
                                <i class="fa fa-check-circle"></i>
                                {l s='In stock' mod='customerviewedproducts'}
                            </div>
                        {/if}
                    </div>
                </article>
            </div>
        {/foreach}
    </div>
</div>

{if $carousel}
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.cvp-carousel').owlCarousel({
        loop: {if $carousel_settings.loop}true{else}false{/if},
        margin: {$carousel_settings.margin},
        nav: {if $carousel_settings.nav}true{else}false{/if},
        dots: {if $carousel_settings.dots}true{else}false{/if},
        responsive: {
            0: { items: {$carousel_settings.responsive.0.items} },
            768: { items: {$carousel_settings.responsive.768.items} },
            992: { items: {$carousel_settings.responsive.992.items} },
            1200: { items: {$carousel_settings.responsive.1200.items} }
        },
        navText: [
            '<i class="fa fa-chevron-left"></i>',
            '<i class="fa fa-chevron-right"></i>'
        ]
    });
});
</script>
{/if}
{/if}