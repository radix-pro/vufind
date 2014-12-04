<!-- Uj: based on "dspace.xsl", "ndltd.xsl", "nlm_ojs.xsl" (dc => elib) -->
<!-- available fields are defined in solr/biblio/conf/schema.xml -->

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
    xmlns:elib="http://purl.org/dc/elements/1.1/"
    xmlns:php="http://php.net/xsl"
    xmlns:xlink="http://www.w3.org/2001/XMLSchema-instance">
    <xsl:output method="xml" indent="yes" encoding="utf-8"/>
    <xsl:param name="collection"></xsl:param>
    <xsl:param name="institution"></xsl:param>
    <xsl:param name="building"></xsl:param>

    <xsl:template match="oai_dc:elib">
        <add>
            <doc>

                <!-- ID -->
                <!-- Important: This relies on an <identifier> tag being injected by the OAI-PMH harvester. -->
                <field name="id">
                    <xsl:value-of select="//elib:identifier"/>
                </field>

                <!-- RECORDTYPE -->
                <field name="recordtype">elib</field>

                <!-- ALLFIELDS -->
                <field name="allfields">
                    <xsl:value-of select="normalize-space(string(//oai_dc:elib))"/>
                </field>

                <!-- COLLECTION -->
                <xsl:if test="//elib:collection">
                    <field name="collection">
                        <xsl:value-of select="//elib:collection" />
                    </field>
                </xsl:if>
                <xsl:if test="string-length($collection) > 0">
                  <field name="collection">
                    <xsl:value-of select="$collection" />
                  </field>
                </xsl:if>

                <!-- INSTITUTION -->
                <xsl:if test="//elib:institution">
                    <field name="building">  <!-- Tmp. facet -->
                        <xsl:value-of select="//elib:institution" />
                    </field>
                </xsl:if>                    <!-- Tmp. facet -->
                <xsl:if test="string-length($building) > 0">
                  <field name="building">
                      <xsl:value-of select="$building" />
                  </field>
                </xsl:if>

                <!-- BUILDING -->
                <!--
                <xsl:if test="string-length($building) > 0">
                  <field name="building">
                    <xsl:value-of select="$building" />
                  </field>
                </xsl:if>
                -->

                <!-- FORMAT -->
                <!-- populating the format field with dc.type instead, see TYPE below.
                     if you like, you can uncomment this to add a hard-coded format
                     in addition to the dynamic ones extracted from the record.
                <field name="format">Article</field>
                -->

                <!-- TYPE (Uj: it is format!) -->
                <xsl:if test="//elib:type">
                    <field name="format">
                        <xsl:value-of select="//elib:type" />
                    </field>
                </xsl:if>

                <!-- LANGUAGE -->
                <xsl:if test="//elib:language">
                    <xsl:for-each select="//elib:language">
                        <xsl:if test="string-length() > 0">
                            <field name="language">
                                <xsl:value-of select="php:function('VuFind::mapString', normalize-space(string(.)), 'language_map_iso639-1.properties')"/>
                            </field>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>

                <!-- Journal general tags -->
                <xsl:if test="//elib:journal_title">
                    <!-- For V.U. (to ignore in main list)
                    <field name="container_title">
                    -->
                    <!-- Controlled by title/title_short
                    <field name="container_reference">
                        <xsl:value-of select="//elib:journal_title" />
                    </field>
                    -->
                </xsl:if>
                <xsl:if test="//elib:journal_issn">
                    <field name="issn">
                        <xsl:value-of select="//elib:journal_issn" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:journal_volume">
                    <field name="container_volume">
                        <xsl:value-of select="//elib:journal_volume" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:journal_issue">
                    <field name="container_issue">
                        <xsl:value-of select="//elib:journal_issue" />
                    </field>
                </xsl:if>
                <!-- Combined with container_title (for V.U.)
                <xsl:if test="//elib:identifier2">
                    <field name="container_reference">
                        <xsl:value-of select="//elib:identifier2" />
                    </field>
                </xsl:if>
                -->
                <xsl:if test="//elib:page_start">
                    <field name="container_start_page">
                        <xsl:value-of select="//elib:page_start" />
                    </field>
                </xsl:if>

                <!-- There are no this fields in "schema.xml" 
                <xsl:if test="//elib:journal_number">
                    <field name="container_number">
                        <xsl:value-of select="//elib:journal_number" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:page_end">
                    <field name="container_end_page">
                        <xsl:value-of select="//elib:page_end" />
                    </field>
                </xsl:if>
                -->

                <!-- PUBLISHER -->
                <xsl:if test="//elib:journal_publ[normalize-space()]">
                    <field name="publisher">
                        <xsl:value-of select="//elib:journal_publ[normalize-space()]"/>
                    </field>
                </xsl:if>

                <!-- PUBLISHDATE -->
                <xsl:if test="//elib:journal_year">
                    <field name="publishDate">
                        <xsl:value-of select="substring(//elib:journal_year, 1, 4)"/>
                    </field>
                    <field name="publishDateSort">
                        <xsl:value-of select="substring(//elib:journal_year, 1, 4)"/>
                    </field>
                </xsl:if>

                <!-- TITLE -->
                <xsl:if test="//elib:title[normalize-space()]">
                    <field name="title">
                        <xsl:value-of select="//elib:title[normalize-space()]"/>
                        <!-- Uj -->
                        <xsl:for-each select="//elib:type">
                        <xsl:if test="contains(., 'Article')">
                             // <xsl:value-of select="//elib:journal_title[normalize-space()]"/>
                        </xsl:if>
                        </xsl:for-each>
                    </field>
                    <field name="title_short">
                        <xsl:value-of select="//elib:title[normalize-space()]"/>
                    </field>
                    <field name="title_full">
                        <xsl:value-of select="//elib:title[normalize-space()]"/>
                    </field>
                    <field name="title_sort">
                        <xsl:value-of select="php:function('VuFind::stripArticles', string(//elib:title[normalize-space()]))"/>
                        <!--
                        <xsl:value-of select="//elib:title[normalize-space()]"/>
                        -->
                    </field>
                </xsl:if>

                <!-- DESCRIPTION -->
                <xsl:if test="//elib:description">
                    <field name="description">
                        <xsl:value-of select="//elib:description" />
                    </field>
                </xsl:if>

                <!-- SUBJECT (Uj: keyword => subject => topic) -->
                <xsl:if test="//elib:keywords">
                    <xsl:for-each select="//elib:keywords">
                        <xsl:if test="string-length() > 0">
                            <field name="topic">
                                <xsl:value-of select="normalize-space()"/>
                            </field>
                            <field name="topic_facet">  <!-- Uj -->
                                <xsl:value-of select="normalize-space()"/>
                            </field>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>
                
                <!-- AUTHOR (Uj: creator => authors) -->
                <xsl:if test="//elib:authors">
                    <xsl:for-each select="//elib:authors">
                        <xsl:if test="normalize-space()">
                            <!-- author is not a multi-valued field, so we'll put
                                 first value there and subsequent values in author2.
                             -->
                            <xsl:if test="position()=1">
                                <field name="author">
                                    <xsl:value-of select="normalize-space()"/>
                                </field>
                                <field name="author-letter">
                                    <xsl:value-of select="normalize-space()"/>
                                </field>
                            </xsl:if>
                            <xsl:if test="position()>1">
                                <field name="author2">
                                    <xsl:value-of select="normalize-space()"/>
                                </field>
                            </xsl:if>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>

                <!-- Literature references (no this field in "schema.xml") 
                <xsl:if test="//elib:references">
                    <xsl:for-each select="//elib:references">
                        <xsl:if test="string-length() > 0">
                            <field name="references">
                                <xsl:value-of select="normalize-space()"/>
                            </field>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>
                -->

                <!-- URL -->
                <xsl:for-each select="//elib:files">
                    <xsl:if test="contains(., '://')">
                        <field name="url">
                            <xsl:value-of select="." />
                        </field>
                    </xsl:if> 
                </xsl:for-each>

                <!-- HIERARCHY -->
                <xsl:if test="//elib:hierarchy_top_id">
                    <field name="hierarchy_top_id">
                        <xsl:value-of select="//elib:hierarchy_top_id" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:hierarchy_top_title">
                    <field name="hierarchy_top_title">
                        <xsl:value-of select="//elib:hierarchy_top_title" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:hierarchy_parent_id">
                    <field name="hierarchy_parent_id">
                        <xsl:value-of select="//elib:hierarchy_parent_id" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:hierarchy_parent_title">
                    <field name="hierarchy_parent_title">
                        <xsl:value-of select="//elib:hierarchy_parent_title" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:is_hierarchy_id">
                    <field name="is_hierarchy_id">
                        <xsl:value-of select="//elib:is_hierarchy_id" />
                    </field>
                </xsl:if>
                <xsl:if test="//elib:is_hierarchy_title">
                    <field name="is_hierarchy_title">
                        <xsl:value-of select="//elib:is_hierarchy_title" />
                    </field>
                </xsl:if>
                <!-- Browse -->
                <xsl:if test="//elib:hierarchy_top_id">
                <xsl:if test="//elib:hierarchy_top_title">
                    <field name="hierarchy_browse">
                        <xsl:value-of select="//elib:hierarchy_top_title" />{{{_ID_}}}<xsl:value-of select="//elib:hierarchy_top_id" />
                    </field>
                </xsl:if>
                </xsl:if>

            </doc>
        </add>
    </xsl:template>

</xsl:stylesheet>
