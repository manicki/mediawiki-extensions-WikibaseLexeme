Then(/^Forms header should be there$/) do
  expect(on(LexemePage).forms_header?).to be true
end

Then(/^Forms container should be there$/) do
  expect(on(LexemePage).forms_container?).to be true
end

Then(/^I see at least one Form$/) do
  expect(on(LexemePage).forms.count).to be > 0
end


Then(/^for each Form there is a representation and an ID$/) do
  #todo: this only checks if there is at least one id and representation
  expect(on(LexemePage).form_representation?).to be true
  expect(on(LexemePage).form_id?).to be true
end

Then(/^for each Form there is a statement list$/) do
  on(LexemePage).forms.each do |form|
    expect(form.statements?).to be true
  end
end

Then(/^each representation is enclosed in tag having lang attribute with "(.+)" as a value$/) do |value|
  #todo: this only checks if there is at least one lang attribute
  on(LexemePage).form_representation_element.attribute('lang').should == value
end

Given(/^for each Form there is a grammatical feature list$/) do
  on(LexemePage).forms.each do |form|
    expect(form.grammatical_feature_list?).to be true
  end
end

When(/^I click the Forms list add button$/) do
  on(LexemePage).add_lexeme_form_element.when_visible.click
  @form_I_am_currently_editing = on(LexemePage).forms[-1]
end

When(/^I enter "(.+)" as the form representation$/) do |representation|
  @form_I_am_currently_editing.representation_input_element.when_visible.clear
  @form_I_am_currently_editing.representation_input = representation
end

When(/^I save the Form$/) do
  # TODO: Had some problems here with element clickability, but failed to reproduce. Fix is probably needed
  @form_I_am_currently_editing.save_element.when_visible.click
end

Then(/^"(.+)" should be displayed as a representation of the Form$/) do |representation|
  @form_I_am_currently_editing.representation_element.text.should == representation
end

Given(/^I have a Lexeme with a Form$/) do
  step 'I have a Lexeme to test' # TODO: implement once Forms are storable
end

When(/^I click on the first Form's edit button$/) do
  @form_I_am_currently_editing = on(LexemePage).forms[0]
  @form_I_am_currently_editing.edit_element.when_visible.click
end

When(/^I select the test item as the grammatical feature$/) do
  @form_I_am_currently_editing.grammatical_features_input_element.send_keys(@item_under_test['label'])
  @form_I_am_currently_editing.grammatical_feature_selection_first_option_element.when_visible.click
end

Then(/^I should see the item's label in the list of grammatical features of the Form$/) do
  Watir::Wait.until { @form_I_am_currently_editing.grammatical_feature?(@item_under_test['label']) }

  expect(@form_I_am_currently_editing.grammatical_feature?(@item_under_test['label'])).to be true
end

When(/^I cancel the editing of the Form$/) do
  @form_I_am_currently_editing.cancel_element.when_visible.click
end

Then(/^I don't see the Form$/) do
  expect(@form_I_am_currently_editing.exists?).to be false
end

When(/^I click add statement on the Form$/) do
  @form_I_am_currently_editing.statement_group.add_statement_element.when_visible.click
  @statement_I_am_currently_editing = @form_I_am_currently_editing.statement_group.statements[-1]
end

When(/^I save the statement$/) do
  @statement_I_am_currently_editing.save_element.when_visible.click
end

Then(/^I see (.+?)=(.+?) statement in the Form statement list$/) do |handle, property_value|
  property_label = @properties[handle]['label']
  Watir::Wait.until(timeout = 5) do
    @form_I_am_currently_editing.statement_group.statement_with_value?(property_label, property_value)
  end

  expect(@form_I_am_currently_editing.statement_group.statement_with_value?(property_label, property_value)).to be true
end

Given(/^a grammatical feature exists for the first Form of the Lexeme$/) do
  # TODO: the grammatical feature to remove should be added in the backend once Forms can be stored
  step 'I have an item to test'
  step 'I click on the first Form\'s edit button'
  step 'I select the test item as the grammatical feature'
  step 'I save the Form'
end

When(/^I remove the first grammatical feature of the first Form$/) do
  gf_to_delete = on(LexemePage).forms[0].grammatical_features[0]
  @grammatical_feature_to_delete = gf_to_delete.text

  gf_to_delete.delete_button_element
      .when_visible
      .click
end

Then(/^the first Form should no longer have the removed grammatical feature$/) do
  expect(@form_I_am_currently_editing.grammatical_feature?(@grammatical_feature_to_delete)).to be false
end
