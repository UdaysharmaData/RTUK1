## Changes to be made on the database

### Role Schema
- Changes to be made
```
- Rename name to slug and add a unique constraint to the attribute.
- Rename display_name to name.
```
- Resulting Schema
```
    $table->string('slug'm 20)->unique();
    $table->string('name', 30);
```

### Site Schema
- Changes to be made
```
- Add unique constraint to the domain attribute.
```
- Resulting Schema
```
    $table->increments('id');
    $table->string('domain')->unique();
    $table->string('name');
    $table->boolean('status');
    $table->timestamps();
```

### User Schema
- Changes to be made
```
- Make company attribute nullable and set the max string length to 30.

- Make last_name attribute nullable and set the max string length to 30.

- Set the max string length of the first_name to 30.

- Rename defaut_site_id to site_id and add the comment "The user default site" beside.

- The company attribute is set only for users of roles charity and charity_user. Remove it from this table since
it can be obtained from the charity title on the charities table.

```
- Resulting Schema
```
    $table->string('last_name', 30)->nullable();
    $table->string('company', 30)->nullable();
```

### Charity Schema
- Changes to be made
```
- Rename title attribute to name and set the max string length to 60.
- Remove the attributes video and supporters_video from the schema
- Make video_id and supporters_video_id nullable and set the max string length to 60
- Make website, color_header and color2_header attributes nullable.
- Also, the video and supporters_video attributes are not needed anymore.
- Rename cc_integration_disabled to cc_integration

```
- Resulting Schema
```
    $table->string('name', 60);
    $table->string('video_id', 60)->nullable();
    $table->string('supporters_video_id', 60)->nullable();
```

### Meta Schema
- Changes to be made
```
- Make the attributes page_id, charity_id, event_id, and partner_id nullable

```
- Resulting Schema
```
    $table->integer('page_id')->nullable();
    $table->integer('charity_id')->nullable();
    $table->integer('event_id')->nullable();
    $table->integer('partner_id')->nullable();
```

### Charity Data Schema
- Changes to be made
```
- This table keeps charity data for different websites. The attributes image_slider, description, mission_values video, video_id and website should be removed from the charity table since it is redundant here.
- Also, the video attribute is not needed anymore.

```
- Resulting Schema
```
    $table->integer('page_id')->nullable();
    $table->integer('charity_id')->nullable();
    $table->integer('event_id')->nullable();
    $table->integer('partner_id')->nullable();
```

### Charity Signups Schema
- Changes to be made
```
- Rename sector attribute to category_id and make it a foreign_key referencing the categories schema.
- Remove the terms_conditions and privacy_policy attributes as they are not needed.


```
## Changes made on the database

Charity Listings, Listing Pages and Event Page Listings sections with their tables and how data can be migrated from the current database to the new database. Some tables have been renamed. Endeavor to read the comments under each table (migration file) before reading it's "Import Notes" below as it will help to better understand the "Import Notes".

### Charity Listings
- partner_listings -> charity_listings

- charity_partner_listings -> charity_charity_listing

- partner_listing_ads

#### Import Notes

#### partner_listings -> charity_listings
- The partner_listings table has been renamed to charity_listings.

- The charities (array of charity ids) saved under the partner_charities and secondary_partner_charities attributes have been moved to the charity_charity_listing table.

- Export the data from the partner_listings table and import it on the charity_listings table while unseting the partner_charities and secondary_partner_charities attributes.

#### charity_partner_listings -> charity_charity_listing
- The charity_partner_listings has been renamed to charity_charity_listing.

- Loop through the data exported from the partner_listings table. For each record, loop through the partner_charities and the secondary_partner_charities, and create a record for these charities on the charity_charity_listing table with the type primary_partner and secondary_partner respectively. Set the url attribute to null while doing this. Once done, loop through the exported data of the charity_partner_listings (contains custom urls) table and update the url attribute of the charity_charity_listings table (for matching records) for partner and secondary charities (of a charity listing) having a custom url.

- For two_year charities, loop through the exported data from the charity_partner_listings table and save the records having the type "2_year" on the charity_charity_listings table.

#### partner_listing_ads
- 

### Listing Pages
- listings_pages -> listing_pages

- listings_pages_charities -> listing_page_charities.

- event_page_listings_pages -> removed and the nullable listing_page_id attribute added to event_pages table

#### Import Notes

#### listing_pages -> listing_pages
- Export the data from the listings_pages table.

- The attributes charity_id, logo, banner_image, background_image, include_2_year_members, partner_listing_description have been removed from this table since they are redundant on the charity_listings (replaced charity_partner_listings) table.

- Import the exported data without setting the redundant attributes mentionned above.

- Though we shall loss the data saved under the redundant attributes of the listings_pages table, it won't be much of a problem since a copy of it (ListingsPage#L107-L111) can be found under the same attributes under the charity_listings (replaced charity_partner_listings) table. Some data of these redundant attributes under the charity_listings table have been updated (it is the more accurate and recently used data), consequently it might not match with the data on it's equivalent attribute on the listings_pages table. Loosing this data won't affect our application.

#### listings_pages_charities -> listing_page_charities

- The charities attribute has been renamed to charity_id.

-  Loop through the exported data. For each record loop through the charities attribute and save it on the listing_page_charities table (new database) alongside it's type.

#### event_page_listings_pages -> removed and the nullable listing_page_id attribute added to event_pages table

On the previous database:

- Add a nullable listing_page_id column to the event_pages table.

- Loop through the records on the event_page_listings_pages table. For each record, get the event page based on the event_page_id, charity_id and event_id (ensure data integrity) and update the recently added listing_page_id attribute (on the event_pages record) with the value of the listing_page_id (from the event_page_listings_pages record).

- Export the data on the event_pages table and import it on the new database.

- NB: Adding the nullable listing_page_id attribute to the event_pages table and with the use of the charity_charity_listing table (new database), the event_page_listings_pages was no longer useful and therefore, was removed. These changes helps to reduce the quantity of data saved in the database.



### Event Page Listings

- event_page_listings

- event_page_event_page_listings -> event_page_event_page_listing

- event_category_event_page_listings -> 
event_category_event_page_listing

- event_page_event_category_event_page_listing (added)


#### Import Notes

#### event_page_listings

- The event pages (array of event page ids) saved under the featured_event_pages attribute has been moved to the event_page_event_page_listing table.

#### event_page_event_page_listings -> event_page_event_page_listing

- The event_page_event_page_listings table (on the previous database) was used to save featured event pages custom images and videos.

- It has been renamed to event_page_event_page_listing and it is now used to save both the featured event pages of the event page listing (former featured_event_pages attribute on event_page_listings table) and the custom images & videos (on the former event_page_event_page_listings table).

- Loop through the exported data of the event_page_listings. For each record, loop through the featured_event_pages attribute and create a new record on the event_page_event_page_listing table. Set the image and video attributes to null while doing this. Once done, loop through the exported data of the event_page_event_page_listings table and update the image and video attribute (for matching records).


#### event_category_event_page_listings -> event_category_event_page_listing

- The event pages (array of event pages ids) saved under the event_pages attribute have been moved to the event_page_event_category_event_page_listing table.


#### event_page_event_category_event_page_listing (added)

- Loop through the data exported from the event_page_listings table. For each record, loop through the event_pages attribute and create a new record under the event_page_event_category_event_page_listing table.


