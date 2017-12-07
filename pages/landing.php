<!-- The complete container -->
<div class="row">

    <!-- Content -->
    <div class="col-md-12 landing-container">
        <!-- Top row -->
        <div class="row">
            <div class="col-md-12"> <!-- this creates horizontal margins w.r.t. container -->
                <div class="col-md-6">
                    <div class="col-md-12 landing-box blue bg-info">  <!-- this creates horizontal margins w.r.t. next column -->
                        <h1>GEMMER</h1>
                        <ul style="list-style-type:square">
                            <li>Complements the existing web-based databases and visualization tools for budding yeast</li>
                            <li>Provides <i>high-quality</i> visualizations of interaction networks for user-specified gene(s)</li>
                            <li>Highlights within such visualizations information on function, expression, timing and network importance</li>
                            <li>Goes beyond "hairball" networks to provide rational visualizations</li>
                        </ul>
                        See the <a href="index.php?id=documentation">documentation</a> for more detailed information
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="col-md-12 landing-box green">  <!-- this creates horizontal margins w.r.t. next column -->
                        <h1>Data integration</h1>
                        <p>GEMMER integrates data from various sources into a single database</p>
                        <ul style="list-style-type:square">
                            <li>
                                <span style="display:inline-block; width: 100px;">
                                    <a href="https://www.yeastgenome.org/" target="blank">SGD</a>
                                </span>
                                <span style="display:inline-block; width: 330px;">
                                    Interactome and experimental evidence
                                </span>
                            </li>
                            <li>
                                <span style="display:inline-block; width: 100px;">
                                    <a href="http://cyclops.ccbr.utoronto.ca/" target="blank">CYCLoPs</a>
                                </span>
                                <span style="display:inline-block; width: 330px;">
                                    Relative abundance and localization
                                </span>
                            </li>
                            <li>
                                <span style="display:inline-block; width: 100px;">
                                    <a href="https://yeastgfp.yeastgenome.org/" target="blank">YeastGFP</a>
                                </span>
                                <span style="display:inline-block; width: 330px;">
                                Absolute abundance and localization
                                </span>
                            </li>
                            <li>
                                <span style="display:inline-block; width: 100px;">
                                    <a href="http://www.sceptrans.org" target="blank">SCEPTRANS</a>
                                </span>
                                <span style="display:inline-block; width: 330px;">
                                    Timing of peak transcription during the cell cycle
                                </span>
                            </li>
                            <li>
                                <span style="display:inline-block; width: 100px;">
                                    <a href="#" target="blank">Yeast 7.6</a>
                                </span>
                                <span style="display:inline-block; width: 330px;">
                                    List of metabolic enzymes
                                </span>
                            </li>
                        </ul>
                        The <a href="index.php?id=database" target="blank">database</a> may be browsed on a gene-by-gene basis
                    </div>
                </div>
            </div>
        </div>

        <div class="row empty-row-10"></div>

        <!-- Middle row -->
        <div class="row">

            <div class="col-md-12"> <!-- this creates horizontal margins w.r.t. container  -->

                <!-- Middle left -->
                <div class="col-md-6">
                    <div class="col-md-12">
                        <div class="row landing-row red">
                            <h1>Network visualizations</h1>
                            User queries result in interactomes that may be visualized with various layouts <br/>
                            See the <a href="index.php?id=documentation">examples</a> for more inspiration
                        </div>

                        <div class="row landing-box demo">
                            <div id="carousel-left" class="carousel slide" data-ride="carousel">
                                <!-- https://www.w3schools.com/bootstrap/bootstrap_carousel.asp -->
                                <!-- Indicators -->
                                <ol class="carousel-indicators">
                                    <li data-target="#carousel-left" data-slide-to="0" class="active"></li>
                                    <li data-target="#carousel-left" data-slide-to="1"></li>
                                    <li data-target="#carousel-left" data-slide-to="2"></li>
                                </ol>

                                <!-- Wrapper for slides -->
                                <div class="carousel-inner">
                                    <div class="item active">
                                        <h3>Interactive, clustered D3.js</h3>
                                        <img src="img/demo_d3_clustered.png" alt="Clustered D3.js layout" class="img-responsive center-block full">
                                    </div>

                                    <div class="item">
                                        <h3>Interactive, clustered Cola.js</h3>
                                        <img src="img/demo_d3cola.svg" alt="D3.js + cola.js layout" class="img-responsive center-block full">
                                    </div>

                                    <div class="item">
                                        <h3>Interactive D3.js Hierarchical edge bundles</h3>
                                        <img src="img/demo_d3_hierarchical_edge_bundles.svg" alt="Hierarchical edge bundling with D3.js" class="img-responsive center-block full">
                                    </div>
                                </div>

                                <!-- Left and right controls -->
                                <a class="left carousel-control" href="#carousel-left" data-slide="prev">
                                    <span class="glyphicon glyphicon-chevron-left"></span>
                                    <span class="sr-only">Previous</span>
                                </a>
                                <a class="right carousel-control" href="#carousel-left" data-slide="next">
                                    <span class="glyphicon glyphicon-chevron-right"></span>
                                    <span class="sr-only">Next</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle right -->
                <div class="col-md-6">
                    <div class="col-md-12">
                        <div class="row landing-row purple">
                            <h1>Data export and modeling</h1>
                            Visualized interactomes may be exported in a variety of formats for further analysis
                        </div>

                        <div class="row landing-box demo">
                            <div class="item active">
                                <h3>Export network to Excel, JSON, GraphML</h3>
                                <p>For offline usage and further analysis by Gephi and Cytoscape</p>
                                <div class="row empty-row-50"></div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <img src="img/noun/excel_green.svg" class="img-responsive center-block" width=40%>
                                    </div>
                                    <div class="col-md-4">
                                        <img src="img/noun/json_purple.svg" class="img-responsive center-block" width=40%>
                                    </div>
                                    <div class="col-md-4">
                                        <img src="img/noun/graph_orange.svg" class="img-responsive center-block" width=40%>
                                    </div>
                                </div>
                                <div class="row empty-row-50"></div>
                                <div class="row">
                                    <img src="img/noun/arrow_down_black.svg" class="img-responsive center-block" width=10%>
                                </div>
                                <div class="row empty-row-50"></div>
                                <div class="row">
                                    <div class="col-md-2"></div>
                                    <div class="col-md-4">
                                        <img src="img/Gephi-logo.png" class="img-responsive center-block" width=100%>
                                    </div>
                                    <div class="col-md-4">
                                        <img src="img/cytoscape-logo.png" class="img-responsive center-block" width=100%>
                                    </div>
                                    <div class="col-md-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Margin -->
        <div class="row empty-row"></div>

    <!-- End content column -->
    </div>

</div>


<h3>GEMMER runs best on modern, up-to-date browsers</h3>
<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-2"><img src="img/browsers/chrome.png" width="100px"></div>
    <div class="col-md-2"><img src="img/browsers/firefox.png" width="100px"></div>
    <div class="col-md-2"><img src="img/browsers/safari.png" width="100px"></div>
    <div class="col-md-3"></div>
</div> 