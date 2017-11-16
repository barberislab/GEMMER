<!-- The complete container -->
<div class="row">

    <!-- Content -->
    <div class="col-md-12 landing-container">
        <!-- Margin -->
        <div class="row empty-row"></div>

        <!-- Top row -->
        <div class="row">
            <div class="col-md-12"> <!-- this creates horizontal margins w.r.t. container -->
                <div class="col-md-6">
                    <div class="col-md-12 landing-box blue bg-info">  <!-- this creates horizontal margins w.r.t. next column -->
                        <h1>GEMMER</h1>
                        <ul style="list-style-type:square">
                            <li>Complements the existing array of databases and visualization tools for budding yeast</li>
                            <li>Provides <i>high-quality</i> visualizations of interaction networks for user-specified genes</li>
                            <li>Highlights within such visualizations information on function, expression, timing and network importance.</li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="col-md-12 landing-box green">  <!-- this creates horizontal margins w.r.t. next column -->
                        <h1>Data integration</h1>
                        from varied sources 
                        <ul style="list-style-type:square">
                            <li><span style="display:inline-block; width: 200px;">SGD</span>
                            <span style="display:inline-block; width: 100px;">SGD</span></li>
                            <li>YeastGFP</li>
                            <li>CYCLoPs</li>
                            <li>SCEPTRANS</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        <!-- Middle row -->
        <div class="row">

            <div class="col-md-12"> <!-- this creates horizontal margins w.r.t. container  -->

                <!-- Middle left -->
                <div class="col-md-6">
                    <div class="row landing-row">
                        <h1>Network visualizations</h1>
                        GEMMER provides various layouts to visualize interaction network
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
                                    <h3>Clustered D3.js layout</h3>
                                    <img src="img/demo1.png" alt="Clustered D3.js layout" class="img-responsive center full"%>
                                </div>

                                <div class="item">
                                    <h3>Clustered Cola.js</h3>
                                    <img src="img/demo1.png" alt="Clustered D3.js layout" class="img-responsive center full">
                                </div>

                                <div class="item">
                                    <h3>Cytoscape.js circular layout</h3>
                                    <img src="img/demo1.png" alt="Clustered D3.js layout" class="img-responsive center full">
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

                <!-- Middle right -->
                <div class="col-md-6">
                    <div class="row landing-row">
                        <h1>Data export and modeling</h1>
                    </div>

                    <div class="row landing-box demo">
                        <div id="carousel-right" class="carousel slide" data-ride="carousel">
                            <!-- https://www.w3schools.com/bootstrap/bootstrap_carousel.asp -->
                            <!-- Indicators -->
                            <ol class="carousel-indicators">
                                <li data-target="#carousel-right" data-slide-to="0" class="active"></li>
                                <li data-target="#carousel-right" data-slide-to="1"></li>
                                <li data-target="#carousel-right" data-slide-to="2"></li>
                            </ol>

                            <!-- Wrapper for slides -->
                            <div class="carousel-inner">
                                <div class="item active">
                                    <h3>Export network to Excel, JSON, GraphML</h3>
                                    <img src="img/noun_excel.svg" class="img-responsive" width=25%> <br/>
                                    For offline usage and further analysis by Gephi and Cytoscape
                                </div>

                                <div class="item">
                                     <h3>Open Excel file in Cytoscape</h3>
                                </div>

                            </div>

                            <!-- Left and right controls -->
                            <a class="left carousel-control" href="#carousel-right" data-slide="prev">
                                <span class="glyphicon glyphicon-chevron-left"></span>
                                <span class="sr-only">Previous</span>
                            </a>
                            <a class="right carousel-control" href="#carousel-right" data-slide="next">
                                <span class="glyphicon glyphicon-chevron-right"></span>
                                <span class="sr-only">Next</span>
                            </a>
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