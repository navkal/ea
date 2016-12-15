import argparse
import ntpath
import csv
from xlsxwriter.workbook import Workbook

def csvsToWorkbook( args ):

  workbook = Workbook( args.workbook_filename )

  icsv=0  # CSV file count

  csv_filenames = args.csv_filenames.split( ',' )

  for csv_filename in csv_filenames:

    # Format name of sheet
    print( "<"+csv_filename+">" )
    basename = ntpath.basename( csv_filename )
    namesplit = basename.split( '_' )
    sheetname = ( "_".join( namesplit[3:] ) ).split( '.' )[0]

    # Add the worksheet to the workbook
    worksheet = workbook.add_worksheet( sheetname )

    # Save data in the worksheet
    with open( csv_filename, mode='r') as csv_file:
      reader = csv.reader( csv_file )
      for r, row in enumerate( reader ):
        for c, col in enumerate( row ):
          worksheet.write( r, c, col )

  workbook.close()



if __name__ == '__main__':
  parser = argparse.ArgumentParser(description='script to convert a collection of CSV files into tabs of an Excel workbook')
  parser.add_argument('-c', dest='csv_filenames',  help='list of CSV filenames')
  parser.add_argument('-w', dest='workbook_filename', help='workbook filename')
  args = parser.parse_args()

  csvsToWorkbook( args )
