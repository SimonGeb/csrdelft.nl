{include file='layout-owee/partials/_header.tpl'}
    <!-- Banner -->
    <section id="banner-small">
        <div class="inner">
            <a href="/"><img src="/plaetjes/layout-owee/Logo.svg"></a>
        </div>
    </section>

    <!-- Wrapper -->
    <section id="wrapper">
        <section class="wrapper spotlight detail style1">
            <div class="inner">
                {$body->view()}
            </div>
        </section>
        <section id="footer">
            <div class="inner">
                <ul class="copyright">
                    <li>&copy; 2016 - C.S.R. Delft</li>
                </ul>
            </div>
        </section>
    </section>
{include file='layout-owee/partials/_footer.tpl'}
