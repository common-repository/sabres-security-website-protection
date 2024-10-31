<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Copyright 2016 Sabres Security Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class SBRS_Helper_Info_Texts {
    private $info_texts = array(
        "Anonymous Browser Protection"               => "This system protects websites from visits originating from anonymous browsers, which many times can be bots or other malicious attempts at gaining access to a website's source code and sensitive data",
        "Application-based Security"                 => "An application-based security system is a program that is installed and is dedicated exclusively to providing security functions. Sabres is application-based, and includes a variety of tools designed to protect, prevent, and eliminate threats from websites built on WordPress",
        "Bots"                                       => "Bots are scripts--automated tasks--that operate over the internet. Bots handle tasks that are repetitive and simple, such as clicking a link, sending emails, or measuring a simple traffic metric. Bots can be useful or malicious, but detecting them on a website is a crucial aspect of protecting websites.",
        "Brute Force Attack"                         => "Brute Force Attacks are some of the most common ways hackers gain access to a website. They do this by attempting as many possible combinations of usernames and passwords until they find a successful one. Web-based security applications such as Sabres can detect these attacks by noticing unusual patterns in log-in attempts and blocking further access until users are verified",
        "CMS Known Exploits Version Vulnerabilities" => "Because WordPress is such a popular tool, there are several vulnerabilities and known attacks that can affect websites. Sabres has an extensive library of these known vulnerabilities and regularly scans websites for these threats. The more websites that install Sabres, the more extensive these libraries become, improving the WordPress community’s protection.",
        "Cryptoblocker"                              => "Cryptoblockers are a type of ransomware that will encrypt files that are smaller than 100MB in size. Unlike other ransomware, it will not provide instructions on how to decrypt files, but will demand payment in return for unblocking files and other data. These can be damaging for a WordPress-based site, as many crucial files such as CSS and HTML5 documents can be significantly smaller",
        "Fake Roler Protection"                      => "",
        "File Defacement"                            => "Sometimes hackers will alter the way a website looks after attacking it to add insult to injury. They do this by chaning files and \"defacing\" them. Sabres protects against file defacement by scanning each file individually and checking against baselines to ensure their integrity",
        "Firewall"                                   => "A network security tool that monitors inbound and outbound traffic based on existing security rules. Firewalls let in trusted traffic, and block traffic from unkown or untrusted sources",
        "GoogleBots"                                 => "Also known as \"spiders\" or crawlers, googlebots discover new and updated webpages in order to add them to Google's index. These bots are not malicious, and are present in most website as trackers that help create a more efficient search engine",
        "Hacker Bot"                                 => "Hacker bots automate many of the tasks hackers would normally have to perform, and do so faster than humans. These programs can find vulnerabilities in sites and attack them using a variety of methods, without requiring human assistance. They have been proven to be lethally effective, and can attack several websites after breaking through a single one. Creating a strong network of protected websites is vital in enhancing website security",
        "Human Detection"                            => "This system can determine whether a visitor to a website is a human visitor, or if it exhibits behaviors more commonly associated with bots.",
        "IMG HOME Defacement"                        => "Hackers may sometimes change a website's visual assets such as homepage images. Many times, these changes are not easily detected, so Sabres performs daily scans of image files to ensure they have not been affected",
        "Known Attack Sources"                       => "This section contains a list of websites and sources of traffic that are known to be malicious and trying to break through your website's defenses. Known Attack Sources can be more detected to better prevent breaches",
        "Malware"                                    => "Any software or application that is designed to damage or disable websites, computers, and computer systems",
        "Network Effect"                             => "The network effect means that protection becomes more powerful and reliable the more users employ it. With Sabres, the network effect becomes amplified as more websites install the plug-in, creating a powerful wall to detect and keep out malicious attacks",
        "Ransomware"                                 => "A type of website attack that will encrypt and threaten to perpetually block or destroy data unless a ransom is paid. Some ransomware simply locks content behind an encrypted wall, but others, such as Cryptoblockers, can actually encrypt the data using an unknowable private key, meaning that information could be lost permanently. Ransomware attacks are becoming more common, so creating a strong network immunity is vital in protecting against them",
        "Scraper"                                    => "These bots crawl through websites stealing or copying data to be used later for illicit and potentially dangerous reasons. Scrapers can take information directly from a website's source code, meaning that unprotected sites could be handing hackers the specific ways to attack them.",
        "Security Configuration Assessment"          => "A scan that will ensure that security configurations are properly set up and optimized for maximum protection",
        "Security Scanner"                           => "A tool that scans for a variety of security vulnerabilities, including bots, hack attempts, and can distinguish between bots and real visitors based on behaviors",
        "Spam Bot"                                   => "A type of bot that is designed to create fake accounts and send spam messages with them. This includes sending messages on comments sections, forums, emails, and contact forms. Spambots can include malware and other viruses in their messages, making them dangerous to websites",
        "Spam Protection"                            => "Spam protection blocks out websites and visitors that are inundating your site with useless junk mail, taking up server space and potentially infecting your website with malware and other viruses",
        "SQL Injections"                             => "An attack that's focused on a website's database system and allow hackers to affect data related to a company's information storage. SQL injections allow hackers to manipulate, steal, and even destroy all the data stored in a website's database.",
        "TFA"                                        => "Two-Factor Authentication is a way to prevent some of the most popular hacking methods. With TFA, users are required to provide to types of credentials when logging in, making it unlikely that a hacker can simply steal a password. Some common ways to add a second layer of credentials include sending a text message or email with a specific code that must be input to gain full access",
        "Uptime"                                     => "The time during which your website remains operational. Uptime is measured as a percentage of the total time a website is hosted. A higher uptime means websites crash less often and are available to visitors",
        "WAF"                                        => "Web-Application Firewalls filter, block, and monitor HTTP traffic heading into and out of a web application. WAFs are different from normal firewalls because they can filter specific web applications, while normal firewalls merely act as gates between servers. WAFs can help protect against SQL injection, XSS, and other increasingly popular malicious attack methods",
        "XSS"                                        => "Also known as cross-site scripting, XSS is a web application vulnerability that allows hackers to inject code into the client-side of an application and insert malicious scripts such as ransomware and malware into legitimate websites and web applications."
    );


    public function get( $name )
    {
        if ( isset( $this->info_texts[ $name ] ) ) {
            return $this->info_texts[ $name ];
        } else {
            foreach ( $this->info_texts as $key => $val) {
                if ( strtolower( $key ) == strtolower( $name )) {
                    return $this->info_texts[ $key ];
                }
            }
        }

        return "";
    }

}